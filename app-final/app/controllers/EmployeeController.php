<?php
namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class EmployeeController extends Controller {

    private User $model;

    public function __construct() {
        parent::__construct();
        $this->model = new User();
    }

    public function index(): void {
        $employees = $this->model->allWithRole();
        $this->view('employees.index', ['title' => 'Employees', 'employees' => $employees]);
    }

    public function create(): void {
        if (!Session::can(['admin','super_admin'])) $this->abort(403);
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        $this->view('employees.create', ['title' => 'Add Employee', 'roles' => $roles]);
    }

    public function store(): void {
        $this->verifyCsrf();
        if (!Session::can(['admin','super_admin'])) $this->abort(403);

        $empId = $this->sanitize($_POST['employee_id'] ?? '');
        if ($this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE employee_id = ?", [$empId])) {
            Session::flash('error', 'Employee ID already exists.');
            $this->redirect('employees/create');
        }

        $data = [
            'employee_id'  => $empId,
            'name'         => $this->sanitize($_POST['name'] ?? ''),
            'email'        => $this->sanitize($_POST['email'] ?? ''),
            'phone'        => $this->sanitize($_POST['phone'] ?? ''),
            'role_id'      => (int)($_POST['role_id'] ?? 3),
            'designation'  => $this->sanitize($_POST['designation'] ?? ''),
            'department'   => $this->sanitize($_POST['department'] ?? ''),
            'join_date'    => $_POST['join_date'] ?: null,
            'password'     => password_hash($_POST['password'] ?? 'Pass@1234', PASSWORD_BCRYPT, ['cost' => 12]),
            'is_active'    => 1,
        ];

        // Face descriptor captured during creation (admin/hr only)
        if (!empty($_POST['face_descriptor']) && Session::can(['admin', 'super_admin', 'hr'])) {
            $raw = json_decode($_POST['face_descriptor'], true);
            if (is_array($raw) && count($raw) === 128) {
                $data['face_descriptor']  = json_encode(array_map('floatval', $raw));
                $data['face_updated_by']  = Session::user()['id'];
                $data['face_updated_at']  = date('Y-m-d H:i:s');
            }
        }

        $id = $this->model->insert($data);
        logActivity('create', 'employees', (int)$id, "Employee: {$data['name']}");
        Session::flash('success', 'Employee added successfully.');
        $this->redirect('employees');
    }

    public function show(string $id): void {
        $employee = $this->model->findWithRole((int)$id);
        if (!$employee) $this->abort(404);

        $leadStats = $this->db->fetch(
            "SELECT COUNT(*) AS total, SUM(status='closed') AS closed
             FROM leads WHERE assigned_to = ?",
            [(int)$id]
        );
        $visitCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM site_visits WHERE assigned_to = ?", [(int)$id]
        );

        $this->view('employees.view', [
            'title'      => $employee['name'],
            'employee'   => $employee,
            'leadStats'  => $leadStats,
            'visitCount' => $visitCount,
        ]);
    }

    public function edit(string $id): void {
        if (!Session::can(['admin','super_admin','hr'])) $this->abort(403);
        $employee = $this->model->findWithRole((int)$id);
        if (!$employee) $this->abort(404);
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        $this->view('employees.edit', [
            'title'    => 'Edit Employee',
            'employee' => $employee,
            'roles'    => $roles,
        ]);
    }

    public function update(string $id): void {
        $this->verifyCsrf();
        if (!Session::can(['admin','super_admin','hr'])) $this->abort(403);

        $data = [
            'name'        => $this->sanitize($_POST['name'] ?? ''),
            'email'       => $this->sanitize($_POST['email'] ?? ''),
            'phone'       => $this->sanitize($_POST['phone'] ?? ''),
            'role_id'     => (int)($_POST['role_id'] ?? 3),
            'designation' => $this->sanitize($_POST['designation'] ?? ''),
            'department'  => $this->sanitize($_POST['department'] ?? ''),
            'join_date'   => !empty($_POST['join_date']) ? $_POST['join_date'] : null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $this->model->update((int)$id, $data);
        logActivity('update', 'employees', (int)$id, 'Employee updated');
        Session::flash('success', 'Employee updated.');
        $this->redirect("employees/{$id}");
    }

    public function delete(string $id): void {
        $this->verifyCsrf();
        if (!Session::can(['admin','super_admin'])) $this->abort(403);
        if ((int)$id === Session::user()['id']) {
            Session::flash('error', 'Cannot delete your own account.');
            $this->redirect('employees');
        }
        $this->model->update((int)$id, ['is_active' => 0]);
        logActivity('delete', 'employees', (int)$id, 'Employee deactivated');
        Session::flash('success', 'Employee deactivated.');
        $this->redirect('employees');
    }

    /**
     * Update face descriptor — ONLY admin / super_admin / hr allowed.
     * Receives: { descriptor: Float32Array as plain Array (128 floats) }
     */
    public function updateFace(string $id): void {
        header('Content-Type: application/json');

        // Role guard — only admin, super_admin, hr
        if (!Session::can(['admin', 'super_admin', 'hr'])) {
            echo json_encode(['success' => false, 'message' => 'Permission denied. Only Admin or HR can register faces.']);
            exit;
        }

        $employee = $this->model->find((int)$id);
        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $descriptor = $body['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor) || count($descriptor) !== 128) {
            echo json_encode(['success' => false, 'message' => 'Invalid face descriptor. Please capture again.']);
            exit;
        }

        // Validate all values are float
        foreach ($descriptor as $v) {
            if (!is_numeric($v)) {
                echo json_encode(['success' => false, 'message' => 'Descriptor contains invalid values.']);
                exit;
            }
        }

        $this->db->execute(
            "UPDATE users SET face_descriptor = ?, face_updated_by = ?, face_updated_at = NOW() WHERE id = ?",
            [json_encode(array_map('floatval', $descriptor)), Session::user()['id'], (int)$id]
        );

        logActivity('update', 'employees', (int)$id, 'Face descriptor registered by ' . Session::user()['name']);
        echo json_encode(['success' => true, 'message' => 'Face registered successfully for ' . $employee['name'] . '.']);
        exit;
    }
}
