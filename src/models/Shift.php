<?php
/**
 * ========================================
 * Model: Shift (Ca làm việc)
 * ========================================
 */

class Shift {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function tableExists() {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'shifts'");
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Lấy tất cả ca làm việc
     */
    public function getAll() {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = "SELECT * FROM shifts ORDER BY start_time ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy ca theo ID
     */
    public function getById($id) {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = "SELECT * FROM shifts WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Tạo ca mới
     */
    public function create($data) {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = "INSERT INTO shifts (shift_name, start_time, end_time, break_time, description, status)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['shift_name'],
            $data['start_time'],
            $data['end_time'],
            $data['break_time'] ?? 0,
            $data['description'] ?? '',
            $data['status'] ?? 'Active'
        ]);
    }
    
    /**
     * Cập nhật ca
     */
    public function update($id, $data) {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = "UPDATE shifts SET 
                shift_name = ?,
                start_time = ?,
                end_time = ?,
                break_time = ?,
                description = ?,
                status = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['shift_name'],
            $data['start_time'],
            $data['end_time'],
            $data['break_time'] ?? 0,
            $data['description'] ?? '',
            $data['status'],
            $id
        ]);
    }
    
    /**
     * Xóa ca
     */
    public function delete($id) {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = "DELETE FROM shifts WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}

?>
