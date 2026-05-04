<?php
namespace App\Models;

use Core\Model;

class LeaveRequest extends Model {
    protected string $table = 'leave_requests';

    public function allWithUser(string $filter = 'all'): array {
        $sql = "SELECT l.*, u.name, u.employee_id, u.department
                FROM leave_requests l
                JOIN users u ON u.id = l.user_id";
        $params = [];
        if ($filter !== 'all') {
            $sql .= " WHERE l.status = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY l.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function myLeaves(int $userId): array {
        return $this->db->fetchAll(
            "SELECT l.*, rv.name AS reviewed_by_name
             FROM leave_requests l
             LEFT JOIN users rv ON rv.id = l.reviewed_by
             WHERE l.user_id = ?
             ORDER BY l.created_at DESC",
            [$userId]
        );
    }

    public function pendingCount(): int {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'", []
        );
    }

    public function approve(int $id, int $reviewerId, string $note): void {
        $this->db->execute(
            "UPDATE leave_requests SET status='approved', reviewed_by=?, review_note=?, reviewed_at=NOW() WHERE id=?",
            [$reviewerId, $note, $id]
        );
    }

    public function reject(int $id, int $reviewerId, string $note): void {
        $this->db->execute(
            "UPDATE leave_requests SET status='rejected', reviewed_by=?, review_note=?, reviewed_at=NOW() WHERE id=?",
            [$reviewerId, $note, $id]
        );
    }
}
