<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\Attendance;
use App\Models\LeaveRequest;

class AttendanceController extends Controller {

    private Attendance   $model;
    private LeaveRequest $leaveModel;

    public function __construct() {
        parent::__construct();
        $this->model      = new Attendance();
        $this->leaveModel = new LeaveRequest();
    }

    // ── ADMIN: Daily attendance sheet ────────────────────────────
    public function index(): void {
        if (!Session::can(['admin', 'super_admin', 'manager', 'hr'])) $this->abort(403);

        $date = $_GET['date'] ?? date('Y-m-d');

        // POST: save manual entries
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $records = $_POST['records'] ?? [];
            $adminId = Session::user()['id'];
            foreach ($records as $uid => $data) {
                $status = $data['status'] ?? '';
                if (!$status) continue;
                $this->model->upsert(
                    (int)$uid,
                    $date,
                    $status,
                    $data['check_in']  ?? null,
                    $data['check_out'] ?? null,
                    $data['notes']     ?? null,
                    $adminId
                );
            }
            logActivity('update', 'attendance', 0, "Manual attendance saved for {$date}");
            Session::flash('success', "Attendance saved for {$date}");
            $this->redirect("attendance?date={$date}");
        }

        $employees = $this->model->getByDate($date);
        $this->view('attendance.index', [
            'title'     => 'Attendance — ' . date('d M Y', strtotime($date)),
            'date'      => $date,
            'employees' => $employees,
        ]);
    }

    // ── EMPLOYEE: Self mark with GPS ─────────────────────────────
    public function my(): void {
        $user  = Session::user();
        $today = date('Y-m-d');

        // AJAX POST — mark attendance
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
            header('Content-Type: application/json');
            $lat    = (float)($_POST['latitude']  ?? 0);
            $lon    = (float)($_POST['longitude'] ?? 0);
            $status = $_POST['status'] ?? 'present';
            $note   = trim($_POST['note'] ?? '');

            if (!$lat || !$lon) {
                echo json_encode(['success' => false, 'message' => 'GPS location required. Please enable location access.']);
                exit;
            }

            // Already marked?
            if ($this->model->todayRecord($user['id'], $today)) {
                echo json_encode(['success' => false, 'message' => 'Attendance already marked for today.']);
                exit;
            }

            // Geo-fence check
            $office = $this->db->fetch("SELECT * FROM office_locations WHERE is_active = 1 LIMIT 1");
            $distance = 0;
            if ($office) {
                $distance = $this->haversine($lat, $lon, (float)$office['latitude'], (float)$office['longitude']);
                if ($distance > $office['radius_meters']) {
                    echo json_encode([
                        'success'  => false,
                        'message'  => "You are " . round($distance) . "m away from office. Must be within {$office['radius_meters']}m.",
                        'distance' => round($distance),
                    ]);
                    exit;
                }
            }

            // Auto late detection (after 10:00 AM)
            $hour = (int)date('H');
            $min  = (int)date('i');
            if ($status === 'present' && ($hour > 10 || ($hour === 10 && $min > 0))) {
                $status = 'late';
            }

            $this->model->markSelf($user['id'], $today, $status, date('H:i:s'), $lat, $lon, $distance);
            logActivity('create', 'attendance', $user['id'], "Self marked: {$status}");

            echo json_encode([
                'success'  => true,
                'message'  => "Attendance marked as " . strtoupper(str_replace('_', ' ', $status)) . ". Distance from office: " . round($distance) . "m.",
                'status'   => $status,
                'distance' => round($distance),
            ]);
            exit;
        }

        $existing = $this->model->todayRecord($user['id'], $today);
        $office   = $this->db->fetch("SELECT * FROM office_locations WHERE is_active = 1 LIMIT 1");

        // My history last 30 days
        $month   = date('Y-m');
        $history = $this->model->myHistory($user['id'], $month);

        $this->view('attendance.my', [
            'title'    => 'My Attendance',
            'existing' => $existing,
            'office'   => $office,
            'history'  => $history,
            'month'    => $month,
        ]);
    }

    // ── ADMIN: Monthly report + CSV export ──────────────────────
    public function report(): void {
        if (!Session::can(['admin', 'super_admin', 'manager', 'hr'])) $this->abort(403);

        $month = $_GET['month'] ?? date('Y-m');

        // CSV export
        if (isset($_GET['export'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendance_' . $month . '.csv"');
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date','Employee ID','Name','Department','Status','Check-in','Check-out','GPS Verified','Distance(m)','Method','Notes']);
            foreach ($this->model->exportData($month) as $row) {
                fputcsv($fh, [
                    $row['date'], $row['employee_id'], $row['name'], $row['department'],
                    ucfirst(str_replace('_', ' ', $row['status'])),
                    $row['check_in'] ?? '', $row['check_out'] ?? '',
                    $row['location_verified'] ? 'Yes' : 'No',
                    $row['distance_meters'] ?? '',
                    $row['method'] ?? '',
                    $row['notes'] ?? '',
                ]);
            }
            fclose($fh);
            exit;
        }

        $summary = $this->model->monthlySummary($month);
        $log     = $this->model->monthlyLog($month);

        // Build month picker (last 12 months)
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $ts = mktime(0, 0, 0, (int)date('m') - $i, 1, (int)date('Y'));
            $months[date('Y-m', $ts)] = date('F Y', $ts);
        }

        $this->view('attendance.report', [
            'title'   => 'Attendance Report — ' . date('F Y', strtotime($month . '-01')),
            'month'   => $month,
            'months'  => $months,
            'summary' => $summary,
            'log'     => $log,
        ]);
    }

    // ── ADMIN: Leave management ──────────────────────────────────
    public function leaves(): void {
        if (!Session::can(['admin', 'super_admin', 'manager', 'hr'])) $this->abort(403);

        $filter = $_GET['filter'] ?? 'pending';
        $leaves = $this->leaveModel->allWithUser($filter);
        $counts = [
            'pending'  => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM leave_requests WHERE status='pending'", []),
            'approved' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM leave_requests WHERE status='approved'", []),
            'rejected' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM leave_requests WHERE status='rejected'", []),
        ];

        $this->view('attendance.leaves', [
            'title'  => 'Leave Requests',
            'leaves' => $leaves,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    // ── ADMIN: Review (approve/reject) leave ────────────────────
    public function reviewLeave(string $id): void {
        $this->verifyCsrf();
        if (!Session::can(['admin', 'super_admin', 'manager', 'hr'])) $this->abort(403);

        $action = $_POST['action'] ?? '';
        $note   = trim($_POST['review_note'] ?? '');
        $lid    = (int)$id;

        if (!in_array($action, ['approve', 'reject'])) {
            Session::flash('error', 'Invalid action.');
            $this->redirect('attendance/leaves');
        }

        $lr = $this->db->fetch("SELECT * FROM leave_requests WHERE id = ?", [$lid]);
        if (!$lr) $this->abort(404);

        if ($action === 'approve') {
            $this->leaveModel->approve($lid, Session::user()['id'], $note);
            // Insert absent records for approved leave days
            $start = new \DateTime($lr['from_date']);
            $end   = new \DateTime($lr['to_date']);
            while ($start <= $end) {
                $ds = $start->format('Y-m-d');
                $this->db->execute(
                    "INSERT IGNORE INTO attendance (user_id, date, status, notes, method, marked_by)
                     VALUES (?, ?, 'absent', ?, 'manual', ?)",
                    [$lr['user_id'], $ds, 'Approved Leave: ' . $lr['leave_type'], Session::user()['id']]
                );
                $start->modify('+1 day');
            }
            Session::flash('success', 'Leave approved successfully.');
        } else {
            $this->leaveModel->reject($lid, Session::user()['id'], $note);
            Session::flash('success', 'Leave request rejected.');
        }

        logActivity('update', 'leave_requests', $lid, "Leave {$action}d");
        $this->redirect('attendance/leaves');
    }

    // ── EMPLOYEE: My leaves ──────────────────────────────────────
    public function myLeaves(): void {
        $user = Session::user();

        // POST: Apply for leave
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $from   = $_POST['from_date'] ?? '';
            $to     = $_POST['to_date']   ?? '';
            $type   = $_POST['leave_type'] ?? 'annual';
            $reason = $this->sanitize($_POST['reason'] ?? '');

            if (!$from || !$to) {
                Session::flash('error', 'Please select from and to dates.');
                $this->redirect('attendance/my-leaves');
            }

            $days = (int)(new \DateTime($from))->diff(new \DateTime($to))->days + 1;

            $this->leaveModel->insert([
                'user_id'    => $user['id'],
                'leave_type' => $type,
                'from_date'  => $from,
                'to_date'    => $to,
                'days_count' => $days,
                'reason'     => $reason,
                'status'     => 'pending',
            ]);
            logActivity('create', 'leave_requests', $user['id'], "Applied {$type} leave {$from} to {$to}");
            Session::flash('success', 'Leave application submitted successfully.');
            $this->redirect('attendance/my-leaves');
        }

        $leaves = $this->leaveModel->myLeaves($user['id']);
        $this->view('attendance.my_leaves', [
            'title'  => 'My Leaves',
            'leaves' => $leaves,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R  = 6371000;
        $f1 = deg2rad($lat1);
        $f2 = deg2rad($lat2);
        $df = deg2rad($lat2 - $lat1);
        $dl = deg2rad($lon2 - $lon1);
        $a  = sin($df / 2) ** 2 + cos($f1) * cos($f2) * sin($dl / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function sanitize(mixed $value): string {
        return htmlspecialchars(strip_tags(trim((string)$value)), ENT_QUOTES, 'UTF-8');
    }
}
