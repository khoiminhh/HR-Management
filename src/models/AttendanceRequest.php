<?php

require_once '../src/models/Attendance.php';

class AttendanceRequest {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    private function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS attendance_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            requested_check_in TIME NOT NULL,
            requested_check_out TIME NOT NULL,
            reason TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Pending',
            admin_note TEXT NULL,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_attendance_requests_employee (employee_id),
            KEY idx_attendance_requests_status (status),
            CONSTRAINT fk_attendance_requests_employee
                FOREIGN KEY (employee_id) REFERENCES employees (employee_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->pdo->exec($sql);
    }

    public function create($data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO attendance_requests
                (employee_id, attendance_date, requested_check_in, requested_check_out, reason)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['employee_id'],
            $data['attendance_date'],
            $data['requested_check_in'],
            $data['requested_check_out'],
            $data['reason'],
        ]);
    }

    public function getByEmployee($employeeId, $limit = 10) {
        $stmt = $this->pdo->prepare(
            "SELECT ar.*, u.username AS reviewer_username
             FROM attendance_requests ar
             LEFT JOIN users u ON ar.reviewed_by = u.user_id
             WHERE ar.employee_id = ?
             ORDER BY ar.created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($status = '') {
        $where = '';
        $params = [];
        if ($status !== '') {
            $where = 'WHERE ar.status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare(
            "SELECT ar.*, e.full_name, e.employee_code, d.department_name, p.position_name,
                    u.username AS reviewer_username
             FROM attendance_requests ar
             JOIN employees e ON ar.employee_id = e.employee_id
             LEFT JOIN departments d ON e.department_id = d.department_id
             LEFT JOIN positions p ON e.position_id = p.position_id
             LEFT JOIN users u ON ar.reviewed_by = u.user_id
             $where
             ORDER BY
                CASE ar.status WHEN 'Pending' THEN 0 WHEN 'Approved' THEN 1 ELSE 2 END,
                ar.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM attendance_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function review($id, $status, $reviewedBy, $note = '') {
        $request = $this->getById($id);
        if (!$request || $request['status'] !== 'Pending') {
            return false;
        }

        if ($status === 'Approved') {
            $attendance = new Attendance($this->pdo);
            $attendance->upsertManual(
                $request['employee_id'],
                $request['attendance_date'],
                $request['requested_check_in'],
                $request['requested_check_out'],
                'Approved attendance request #' . (int)$id
            );
        }

        $stmt = $this->pdo->prepare(
            "UPDATE attendance_requests
             SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$status, $note, $reviewedBy, $id]);
    }
}

?>
