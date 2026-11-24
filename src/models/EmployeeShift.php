<?php
/**
 * ========================================
 * Model: EmployeeShift (Gán ca cho nhân viên)
 * ========================================
 */

class EmployeeShift {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function tableExists($table) {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Lấy tất cả gán ca
     */
    public function getAll() {
        if (!$this->tableExists('employee_shifts') || !$this->tableExists('shifts')) {
            return [];
        }

        $sql = "SELECT es.*, e.full_name, e.employee_code, s.shift_name, s.start_time, s.end_time
                FROM employee_shifts es
                JOIN employees e ON es.employee_id = e.employee_id
                JOIN shifts s ON es.shift_id = s.id
                ORDER BY es.start_date DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy ca của nhân viên
     */
    public function getByEmployeeId($employee_id) {
        if (!$this->tableExists('employee_shifts') || !$this->tableExists('shifts')) {
            return [];
        }

        $sql = "SELECT es.*, s.shift_name, s.start_time, s.end_time
                FROM employee_shifts es
                JOIN shifts s ON es.shift_id = s.id
                WHERE es.employee_id = ? AND es.status = 'Active'
                ORDER BY es.start_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Gán ca cho nhân viên
     */
    public function assign($data) {
        if (!$this->tableExists('employee_shifts')) {
            return false;
        }

        $sql = "INSERT INTO employee_shifts (employee_id, shift_id, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['employee_id'],
            $data['shift_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['status'] ?? 'Active'
        ]);
    }
    
    /**
     * Cập nhật gán ca
     */
    public function update($id, $data) {
        if (!$this->tableExists('employee_shifts')) {
            return false;
        }

        $sql = "UPDATE employee_shifts SET 
                employee_id = ?,
                shift_id = ?,
                start_date = ?,
                end_date = ?,
                status = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['employee_id'],
            $data['shift_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['status'],
            $id
        ]);
    }
    
    /**
     * Xóa gán ca
     */
    public function delete($id) {
        if (!$this->tableExists('employee_shifts')) {
            return false;
        }

        $sql = "DELETE FROM employee_shifts WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}

?>
