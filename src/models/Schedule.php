<?php
/**
 * ========================================
 * Model: Schedule (Lịch làm việc)
 * ========================================
 */

class Schedule {
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
     * Lấy tất cả lịch làm việc
     */
    public function getAll() {
        if (!$this->tableExists('schedules')) {
            return [];
        }

        $sql = "SELECT s.*, e.full_name, e.employee_code, sh.shift_name, sh.start_time, sh.end_time
                FROM schedules s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN shifts sh ON s.shift_id = sh.id
                ORDER BY s.schedule_date DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy lịch theo ngày
     */
    public function getByDate($date) {
        if (!$this->tableExists('schedules')) {
            return [];
        }

        $sql = "SELECT s.*, e.full_name, e.employee_code, sh.shift_name, sh.start_time, sh.end_time
                FROM schedules s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN shifts sh ON s.shift_id = sh.id
                WHERE s.schedule_date = ?
                ORDER BY sh.start_time ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy lịch theo nhân viên
     */
    public function getByEmployeeId($employee_id, $month = null, $year = null) {
        if (!$this->tableExists('schedules')) {
            return [];
        }

        if ($month && $year) {
            $sql = "SELECT s.*, sh.shift_name, sh.start_time, sh.end_time
                    FROM schedules s
                    LEFT JOIN shifts sh ON s.shift_id = sh.id
                    WHERE s.employee_id = ? 
                    AND MONTH(s.schedule_date) = ? 
                    AND YEAR(s.schedule_date) = ?
                    ORDER BY s.schedule_date ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employee_id, $month, $year]);
        } else {
            $sql = "SELECT s.*, sh.shift_name, sh.start_time, sh.end_time
                    FROM schedules s
                    LEFT JOIN shifts sh ON s.shift_id = sh.id
                    WHERE s.employee_id = ?
                    ORDER BY s.schedule_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employee_id]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy lịch theo ID
     */
    public function getById($id) {
        if (!$this->tableExists('schedules')) {
            return false;
        }

        $sql = "SELECT s.*, e.full_name, e.employee_code, sh.shift_name
                FROM schedules s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN shifts sh ON s.shift_id = sh.id
                WHERE s.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Kiểm tra lịch đã tồn tại
     */
    public function checkExists($employee_id, $schedule_date, $excludeId = null) {
        if (!$this->tableExists('schedules')) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM schedules 
                WHERE employee_id = ? AND schedule_date = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employee_id, $schedule_date, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employee_id, $schedule_date]);
        }
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Thêm lịch
     */
    public function create($data) {
        if (!$this->tableExists('schedules')) {
            return false;
        }

        $sql = "INSERT INTO schedules (employee_id, schedule_date, shift_id, status, notes)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['employee_id'],
            $data['schedule_date'],
            $data['shift_id'] ?? null,
            $data['status'] ?? 'Scheduled',
            $data['notes'] ?? ''
        ]);
    }
    
    /**
     * Cập nhật lịch
     */
    public function update($id, $data) {
        if (!$this->tableExists('schedules')) {
            return false;
        }

        $sql = "UPDATE schedules SET 
                employee_id = ?,
                schedule_date = ?,
                shift_id = ?,
                status = ?,
                notes = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['employee_id'],
            $data['schedule_date'],
            $data['shift_id'] ?? null,
            $data['status'],
            $data['notes'] ?? '',
            $id
        ]);
    }
    
    /**
     * Xóa lịch
     */
    public function delete($id) {
        if (!$this->tableExists('schedules')) {
            return false;
        }

        $sql = "DELETE FROM schedules WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Tạo lịch hàng loạt cho tháng
     */
    public function createBulkSchedule($employee_id, $month, $year, $shift_id) {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $dayOfWeek = date('w', strtotime($date)); // 0=Sun, 6=Sat
            
            // Bỏ qua thứ 7 và chủ nhật nếu cần
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                continue; // Bỏ qua weekend
            }
            
            if (!$this->checkExists($employee_id, $date)) {
                $this->create([
                    'employee_id' => $employee_id,
                    'schedule_date' => $date,
                    'shift_id' => $shift_id,
                    'status' => 'Scheduled'
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Lấy thống kê lịch theo tháng
     */
    public function getStatsByMonth($month, $year) {
        if (!$this->tableExists('schedules')) {
            return [];
        }

        $sql = "SELECT 
                status,
                COUNT(*) as count
                FROM schedules
                WHERE MONTH(schedule_date) = ? AND YEAR(schedule_date) = ?
                GROUP BY status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }
}

?>
