<?php
/**
 * ========================================
 * Model: Salary
 * ========================================
 */

class Salary {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public static function baseSalaryExpression($alias = 's') {
        return "COALESCE($alias.total_salary, 0) - COALESCE($alias.allowance, 0) + COALESCE($alias.deduction, 0) + COALESCE($alias.insurance, 0)";
    }

    private function selectSql($where = '') {
        return "SELECT s.*,
                       s.salary_id AS id,
                       s.salary_month AS month,
                       s.salary_year AS year,
                       " . self::baseSalaryExpression('s') . " AS base_salary,
                       s.allowance AS allowances,
                       s.deduction AS deductions,
                       s.total_salary AS net_salary,
                       s.calculated_date AS payment_date,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position,
                       d.department_name AS department,
                       e.email,
                       e.phone
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                $where";
    }

    public function getAll() {
        $stmt = $this->pdo->query($this->selectSql("ORDER BY s.salary_year DESC, s.salary_month DESC"));
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM salaries");
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare($this->selectSql("WHERE s.salary_id = ?"));
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByEmployeeId($employee_id) {
        $stmt = $this->pdo->prepare($this->selectSql("WHERE s.employee_id = ? ORDER BY s.salary_year DESC, s.salary_month DESC"));
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }

    public function getByMonthYear($month, $year) {
        $stmt = $this->pdo->prepare($this->selectSql("WHERE s.salary_month = ? AND s.salary_year = ? ORDER BY e.full_name"));
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }

    public function getActiveContractSalary($employeeId, $month, $year) {
        $period = self::salaryPeriodRange((int)$month, (int)$year);
        if (!$employeeId || !$period) {
            return null;
        }

        $sql = "SELECT salary
                FROM contracts
                WHERE employee_id = ?
                  AND status = 'Active'
                  AND start_date <= ?
                  AND (end_date IS NULL OR end_date >= ?)
                ORDER BY start_date DESC, contract_id DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employeeId, $period['end'], $period['start']]);
        $salary = $stmt->fetchColumn();

        return $salary === false ? null : self::toMoney($salary);
    }

    public function getActiveContractsByEmployee() {
        $sql = "SELECT employee_id, start_date, end_date, salary
                FROM contracts
                WHERE status = 'Active'
                  AND salary IS NOT NULL
                  AND salary > 0
                ORDER BY employee_id, start_date DESC, contract_id DESC";
        $stmt = $this->pdo->query($sql);
        $contractsByEmployee = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $contract) {
            $employeeId = (string)$contract['employee_id'];
            $contractsByEmployee[$employeeId][] = [
                'start_date' => $contract['start_date'],
                'end_date' => $contract['end_date'],
                'salary' => self::toMoney($contract['salary']),
            ];
        }

        return $contractsByEmployee;
    }

    public function checkExists($employee_id, $month, $year, $excludeId = null) {
        $sql = "SELECT COUNT(*) AS count
                FROM salaries
                WHERE employee_id = ? AND salary_month = ? AND salary_year = ?";
        $params = [$employee_id, $month, $year];

        if ($excludeId) {
            $sql .= " AND salary_id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function filterSalaries($month = null, $year = null) {
        $where = ["1=1"];
        $params = [];

        if ($month) {
            $where[] = "s.salary_month = ?";
            $params[] = $month;
        }

        if ($year) {
            $where[] = "s.salary_year = ?";
            $params[] = $year;
        }

        $stmt = $this->pdo->prepare($this->selectSql("WHERE " . implode(" AND ", $where) . " ORDER BY s.salary_year DESC, s.salary_month DESC"));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $baseSalary = self::toMoney($data['base_salary'] ?? 0);
        $allowances = self::toMoney($data['allowances'] ?? 0);
        $deductions = self::toMoney($data['deductions'] ?? 0);
        $insurance  = self::toMoney($data['insurance'] ?? 0);
        $netSalary = $baseSalary + $allowances - $deductions - $insurance;

        $sql = "INSERT INTO salaries
                (employee_id, salary_month, salary_year, allowance, deduction, insurance, total_salary, calculated_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $data['employee_id'],
            $data['month'],
            $data['year'],
            $allowances,
            $deductions,
            $insurance,
            $netSalary,
            $data['payment_date'] ?: null,
        ]);
    }

    public function update($id, $data) {
        $baseSalary = self::toMoney($data['base_salary'] ?? 0);
        $allowances = self::toMoney($data['allowances'] ?? 0);
        $deductions = self::toMoney($data['deductions'] ?? 0);
        $insurance  = self::toMoney($data['insurance'] ?? 0);
        $netSalary = $baseSalary + $allowances - $deductions - $insurance;

        $sql = "UPDATE salaries SET
                    employee_id = ?,
                    salary_month = ?,
                    salary_year = ?,
                    allowance = ?,
                    deduction = ?,
                    insurance = ?,
                    total_salary = ?,
                    calculated_date = ?
                WHERE salary_id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $data['employee_id'],
            $data['month'],
            $data['year'],
            $allowances,
            $deductions,
            $insurance,
            $netSalary,
            $data['payment_date'] ?: null,
            $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM salaries WHERE salary_id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalByMonthYear($month, $year) {
        $sql = "SELECT
                    SUM(" . self::baseSalaryExpression('s') . ") AS total_base,
                    SUM(s.allowance) AS total_allowances,
                    SUM(s.deduction) AS total_deductions,
                    SUM(s.insurance) AS total_insurance,
                    SUM(s.total_salary) AS total_net
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                WHERE s.salary_month = ? AND s.salary_year = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetch();
    }

    public function getByDateRange($startMonth, $startYear, $endMonth, $endYear) {
        $stmt = $this->pdo->prepare($this->selectSql(
            "WHERE (s.salary_year > ? OR (s.salary_year = ? AND s.salary_month >= ?))
             AND (s.salary_year < ? OR (s.salary_year = ? AND s.salary_month <= ?))
             ORDER BY s.salary_year DESC, s.salary_month DESC, e.full_name"
        ));
        $stmt->execute([$startYear, $startYear, $startMonth, $endYear, $endYear, $endMonth]);
        return $stmt->fetchAll();
    }

    public function getAvailableMonths() {
        $sql = "SELECT DISTINCT salary_year AS year, salary_month AS month
                FROM salaries
                ORDER BY salary_year DESC, salary_month DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public static function toMoney($value) {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = str_replace(['.', ',', ' ', "\xc2\xa0"], '', $value);
        }

        return round((float)$value, 2);
    }

    public static function salaryPeriodRange($month, $year) {
        $month = (int)$month;
        $year = (int)$year;

        if ($month < 1 || $month > 12 || $year < 1) {
            return null;
        }

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}

?>
