<?php
namespace App\Models;

use Core\Model;

class Attendance extends Model {
    protected string $table = 'attendance';

    /**
     * Get all employees with their attendance for a specific date.
     */
    public function getByDate(string $date): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.employee_id, u.name, u.designation, u.department,
                    a.status, a.check_in, a.check_out, a.notes, a.method,
                    a.location_verified, a.distance_meters
             FROM users u
             LEFT JOIN attendance a ON a.user_id = u.id AND a.date = ?
             WHERE u.is_active = 1
             ORDER BY u.department, u.name",
            [$date]
        );
    }

    /**
     * Upsert attendance record (admin manual mark).
     */
    public function upsert(int $userId, string $date, string $status, ?string $checkIn,
                           ?string $checkOut, ?string $notes, int $markedBy): void {
        $this->db->execute(
            "INSERT INTO attendance (user_id, date, status, check_in, check_out, notes, method, marked_by)
             VALUES (?, ?, ?, ?, ?, ?, 'manual', ?)
             ON DUPLICATE KEY UPDATE
               status    = VALUES(status),
               check_in  = VALUES(check_in),
               check_out = VALUES(check_out),
               notes     = VALUES(notes),
               method    = 'manual',
               marked_by = VALUES(marked_by)",
            [$userId, $date, $status, $checkIn ?: null, $checkOut ?: null, $notes ?: null, $markedBy]
        );
    }

    /**
     * Employee self-mark with GPS.
     */
    public function markSelf(int $userId, string $date, string $status, string $checkIn,
                              float $lat, float $lon, float $distance): void {
        $this->db->execute(
            "INSERT INTO attendance
                (user_id, date, status, check_in, latitude, longitude, distance_meters, location_verified, method)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'face')
             ON DUPLICATE KEY UPDATE
               check_in          = VALUES(check_in),
               latitude          = VALUES(latitude),
               longitude         = VALUES(longitude),
               distance_meters   = VALUES(distance_meters),
               location_verified = 1",
            [$userId, $date, $status, $checkIn, $lat, $lon, round($distance, 2)]
        );
    }

    /**
     * Check if user already marked attendance today.
     */
    public function todayRecord(int $userId, string $date): array|false {
        return $this->db->fetch(
            "SELECT * FROM attendance WHERE user_id = ? AND date = ? LIMIT 1",
            [$userId, $date]
        );
    }

    /**
     * Monthly summary per employee.
     */
    public function monthlySummary(string $month): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.employee_id, u.name, u.department,
                    SUM(a.status = 'present')  AS present,
                    SUM(a.status = 'absent')   AS absent,
                    SUM(a.status = 'late')     AS late,
                    SUM(a.status = 'half_day') AS half_day,
                    COUNT(a.id)                AS total_marked
             FROM users u
             LEFT JOIN attendance a ON a.user_id = u.id
                   AND DATE_FORMAT(a.date,'%Y-%m') = ?
             WHERE u.is_active = 1
             GROUP BY u.id
             ORDER BY u.department, u.name",
            [$month]
        );
    }

    /**
     * Detailed log for a month (limit 300).
     */
    public function monthlyLog(string $month): array {
        return $this->db->fetchAll(
            "SELECT a.date, u.name, u.employee_id, u.department,
                    a.status, a.check_in, a.check_out, a.location_verified,
                    a.distance_meters, a.method, a.notes
             FROM attendance a
             JOIN users u ON u.id = a.user_id
             WHERE DATE_FORMAT(a.date,'%Y-%m') = ?
             ORDER BY a.date DESC, u.name
             LIMIT 300",
            [$month]
        );
    }

    /**
     * Employee personal history.
     */
    public function myHistory(int $userId, string $month): array {
        return $this->db->fetchAll(
            "SELECT * FROM attendance
             WHERE user_id = ? AND DATE_FORMAT(date,'%Y-%m') = ?
             ORDER BY date DESC",
            [$userId, $month]
        );
    }

    /**
     * CSV export data for a month.
     */
    public function exportData(string $month): array {
        return $this->db->fetchAll(
            "SELECT a.date, u.employee_id, u.name, u.department,
                    a.status, a.check_in, a.check_out,
                    a.location_verified, a.distance_meters, a.method, a.notes
             FROM attendance a
             JOIN users u ON u.id = a.user_id
             WHERE DATE_FORMAT(a.date,'%Y-%m') = ?
             ORDER BY a.date DESC, u.name",
            [$month]
        );
    }
}
