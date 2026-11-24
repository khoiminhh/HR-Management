<?php
/**
 * ========================================
 * Model: PartTimer (Quáº£n lĂ½ Part-time)
 * ========================================
 */

class PartTimer {
    private $pdo;

    const SALARY_MAX     = 2340000;
    const SALARY_WARN    = 2200000;
    const BHXH_THRESHOLD = 2340000;
    const BREAK_MINUTES  = 30;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTables();
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

    private function ensureTables() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS part_timers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
                max_hours_per_week DECIMAL(5,2) NOT NULL DEFAULT 30,
                start_date DATE NOT NULL,
                end_date DATE NULL,
                contract_type VARCHAR(50) NOT NULL DEFAULT 'Part-time',
                status VARCHAR(20) NOT NULL DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_part_timers_employee (employee_id),
                INDEX idx_part_timers_status (status),
                CONSTRAINT fk_part_timers_employee
                    FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS part_time_hours (
                id INT AUTO_INCREMENT PRIMARY KEY,
                part_timer_id INT NOT NULL,
                work_date DATE NOT NULL,
                hours DECIMAL(5,2) NOT NULL DEFAULT 0,
                hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                notes VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_part_time_hours_timer_date (part_timer_id, work_date),
                CONSTRAINT fk_part_time_hours_timer
                    FOREIGN KEY (part_timer_id) REFERENCES part_timers(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (PDOException $e) {
            // Existing installations can continue to show a validation error if migration fails.
        }
    }
    
    /**
     * Láº¥y táº¥t cáº£ part-timer
     */
    public function getAll() {
        if (!$this->tableExists('part_timers')) {
            return [];
        }

        $sql = "SELECT pt.*,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position,
                       d.department_name AS department
                FROM part_timers pt
                JOIN employees e ON pt.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                ORDER BY pt.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Láº¥y part-timer theo ID
     */
    public function getById($id) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "SELECT pt.*, e.full_name, e.employee_code, e.email
                FROM part_timers pt
                JOIN employees e ON pt.employee_id = e.employee_id
                WHERE pt.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Láº¥y thĂ´ng tin part-timer theo employee_id
     */
    public function getByEmployeeId($employee_id) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "SELECT pt.*, e.full_name, e.employee_code, e.email
                FROM part_timers pt
                JOIN employees e ON pt.employee_id = e.employee_id
                WHERE pt.employee_id = ? AND pt.status = 'Active'
                ORDER BY pt.id DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetch();
    }
    
    /**
     * Kiá»ƒm tra nhĂ¢n viĂªn cĂ³ pháº£i part-timer khĂ´ng
     */
    public function checkPartTimer($employee_id) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM part_timers 
                WHERE employee_id = ? AND status = 'Active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * ThĂªm part-timer
     */
    public function create($data) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "INSERT INTO part_timers (employee_id, hourly_rate, max_hours_per_week, start_date, end_date, contract_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['employee_id'],
            $data['hourly_rate'],
            $data['max_hours_per_week'] ?? 30,
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['contract_type'] ?? 'Part-time',
            $data['status'] ?? 'Active'
        ]);
    }
    
    /**
     * Cáº­p nháº­t part-timer
     */
    public function update($id, $data) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "UPDATE part_timers SET 
                hourly_rate = ?,
                max_hours_per_week = ?,
                end_date = ?,
                status = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['hourly_rate'],
            $data['max_hours_per_week'] ?? 30,
            $data['end_date'] ?? null,
            $data['status'],
            $id
        ]);
    }
    
    /**
     * XĂ³a part-timer
     */
    public function delete($id) {
        if (!$this->tableExists('part_timers')) {
            return false;
        }

        $sql = "DELETE FROM part_timers WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * ThĂªm giá» cĂ´ng part-time
     */
    public function addWorkHours($data) {
        if (!$this->tableExists('part_time_hours')) {
            return false;
        }

        if (!empty($this->validateWorkHours($data))) {
            return false;
        }

        $billableHours = self::calcBillableHours((float)$data['hours']);
        $amount = $billableHours * (float)$data['hourly_rate'];
        
        $sql = "INSERT INTO part_time_hours (part_timer_id, work_date, hours, hourly_rate, amount, notes)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['part_timer_id'],
            $data['work_date'],
            $billableHours,
            $data['hourly_rate'],
            $amount,
            $data['notes'] ?? ''
        ]);
    }

    public static function calcBillableHours(float $raw_hours): float {
        return max(0, $raw_hours - self::BREAK_MINUTES / 60);
    }

    public function validateWorkHours($data): array {
        $errors = [];

        if (!$this->tableExists('part_time_hours')) {
            return ['Bang gio cong part-time chua san sang.'];
        }

        $partTimerId = (int)($data['part_timer_id'] ?? 0);
        $rawHours = (float)($data['hours'] ?? 0);
        $hourlyRate = (float)($data['hourly_rate'] ?? 0);
        $workDate = $data['work_date'] ?? '';

        if ($partTimerId <= 0) {
            $errors[] = 'Thieu thong tin part-timer.';
        }

        if (!$workDate || strtotime($workDate) === false) {
            $errors[] = 'Ngay lam viec khong hop le.';
        }

        if ($rawHours <= 0 || $rawHours > 12) {
            $errors[] = 'So gio lam phai nam trong khoang 0.5 - 12 gio.';
        }

        if ($hourlyRate <= 0) {
            $errors[] = 'Luong theo gio khong hop le.';
        }

        $billableHours = self::calcBillableHours($rawHours);
        if ($billableHours <= 0) {
            $errors[] = 'So gio tinh luong sau khi tru nghi phai lon hon 0.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $newAmount = $billableHours * $hourlyRate;
        $month = (int)date('m', strtotime($workDate));
        $year = (int)date('Y', strtotime($workDate));
        $summary = $this->calculateTotalSalary($partTimerId, $month, $year);
        $currentAmount = (float)($summary['total_amount'] ?? 0);

        if ($currentAmount + $newAmount > self::SALARY_MAX) {
            $remaining = max(0, self::SALARY_MAX - $currentAmount);
            $errors[] = 'Ca nay vuot gioi han luong thang. Con toi da '
                . number_format($remaining, 0, ',', '.') . ' d.';
        }

        $partTimer = $this->getById($partTimerId);
        $maxHoursPerWeek = (float)($partTimer['max_hours_per_week'] ?? 0);
        if ($maxHoursPerWeek > 0) {
            $weeklyHours = $this->getWeeklyBillableHours($partTimerId, $workDate);
            if ($weeklyHours + $billableHours > $maxHoursPerWeek) {
                $remainingHours = max(0, $maxHoursPerWeek - $weeklyHours);
                $errors[] = 'Ca nay vuot gioi han gio tuan. Con toi da '
                    . number_format($remainingHours, 1) . ' gio tinh luong.';
            }
        }

        return $errors;
    }
    private function getWeeklyBillableHours(int $part_timer_id, string $work_date): float {
        $sql = "SELECT COALESCE(SUM(hours), 0)
                FROM part_time_hours
                WHERE part_timer_id = ?
                AND YEARWEEK(work_date, 1) = YEARWEEK(?, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$part_timer_id, $work_date]);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Láº¥y giá» cĂ´ng part-time
     */
    public function getWorkHours($part_timer_id, $month = null, $year = null) {
        if (!$this->tableExists('part_time_hours')) {
            return [];
        }

        if ($month && $year) {
            $sql = "SELECT * FROM part_time_hours 
                    WHERE part_timer_id = ? 
                    AND MONTH(work_date) = ? 
                    AND YEAR(work_date) = ?
                    ORDER BY work_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$part_timer_id, $month, $year]);
        } else {
            $sql = "SELECT * FROM part_time_hours 
                    WHERE part_timer_id = ? 
                    ORDER BY work_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$part_timer_id]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * TĂ­nh tá»•ng lÆ°Æ¡ng part-time
     */
    public function calculateTotalSalary($part_timer_id, $month, $year) {
        if (!$this->tableExists('part_time_hours')) {
            return ['total_hours' => 0, 'total_amount' => 0];
        }

        $sql = "SELECT 
                SUM(hours) as total_hours,
                SUM(amount) as total_amount
                FROM part_time_hours 
                WHERE part_timer_id = ? 
                AND MONTH(work_date) = ? 
                AND YEAR(work_date) = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$part_timer_id, $month, $year]);
        return $stmt->fetch();
    }

    /**
     * Phan tich trang thai luong va BHXH cua part-timer trong thang.
     */
    public function analyzeSalaryStatus(int $part_timer_id, int $month, int $year, float $hourly_rate): array {
        $summary = $this->calculateTotalSalary($part_timer_id, $month, $year);
        $total_amount = (float)($summary['total_amount'] ?? 0);
        $total_hours = (float)($summary['total_hours'] ?? 0);

        $percent = self::SALARY_MAX > 0 ? round($total_amount / self::SALARY_MAX * 100, 1) : 0;
        $remaining_amount = max(0, self::SALARY_MAX - $total_amount);
        $remaining_hours = $hourly_rate > 0 ? round($remaining_amount / $hourly_rate, 1) : 0;

        if ($total_amount >= self::SALARY_MAX) {
            $status = 'exceeded';
            $message = 'Da dat gioi han ' . number_format(self::SALARY_MAX, 0, ',', '.') . ' d/thang. Khong duoc them gio cong.';
        } elseif ($total_amount >= self::SALARY_WARN) {
            $status = 'warning';
            $message = 'Sap dat gioi han. Con duoc lam toi da '
                . number_format($remaining_amount, 0, ',', '.') . ' d ('
                . $remaining_hours . ' gio nua).';
        } else {
            $status = 'ok';
            $message = '';
        }

        $bhxh_required = false;
        $bhxh_message = '';
        if ($total_amount >= self::BHXH_THRESHOLD) {
            $bhxh_required = true;
            $bhxh_message = 'Thu nhap dat ' . number_format(self::BHXH_THRESHOLD, 0, ',', '.') . ' d, bat buoc dong BHXH.';
        } elseif ($total_amount > 0) {
            $needed_amount = self::BHXH_THRESHOLD - $total_amount;
            $needed_hours = $hourly_rate > 0 ? round($needed_amount / $hourly_rate, 1) : 0;
            $bhxh_message = 'Chua du dieu kien BHXH. Can them '
                . number_format($needed_amount, 0, ',', '.') . ' d (~'
                . $needed_hours . ' gio) de dat nguong '
                . number_format(self::BHXH_THRESHOLD, 0, ',', '.') . ' d/thang.';
        }

        return compact(
            'total_amount',
            'total_hours',
            'percent',
            'status',
            'message',
            'remaining_amount',
            'remaining_hours',
            'bhxh_required',
            'bhxh_message'
        );
    }

    /**
     * Lay cac canh bao part-timer trong thang cho dashboard.
     */
    public function getAllAlerts(int $month, int $year): array {
        $alerts = [];

        foreach ($this->getAll() as $pt) {
            if (($pt['status'] ?? '') !== 'Active') {
                continue;
            }

            $analysis = $this->analyzeSalaryStatus(
                (int)$pt['id'],
                $month,
                $year,
                (float)$pt['hourly_rate']
            );

            if ($analysis['status'] !== 'ok' || $analysis['bhxh_required']) {
                $alerts[] = array_merge($pt, ['analysis' => $analysis]);
            }
        }

        return $alerts;
    }
}

?>

