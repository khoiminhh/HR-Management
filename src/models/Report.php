<?php
/**
 * ========================================
 * Model: Report
 * ========================================
 */

class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function salaryBaseExpression($alias = 's') {
        return "COALESCE($alias.total_salary, 0) - COALESCE($alias.allowance, 0) + COALESCE($alias.deduction, 0) + COALESCE($alias.insurance, 0)";
    }

    public function getEmployeeStatsByStatus() {
        $stmt = $this->pdo->query("SELECT 'Active' AS status, COUNT(*) AS count FROM employees");
        return $stmt->fetchAll();
    }

    public function getEmployeeStatsByDepartment() {
        $sql = "SELECT d.department_name AS department, COUNT(e.employee_id) AS count
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE d.department_name IS NOT NULL AND d.department_name != ''
                GROUP BY d.department_name
                ORDER BY count DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getEmployeeStatsByPosition() {
        $sql = "SELECT p.position_name AS position, COUNT(e.employee_id) AS count
                FROM employees e
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE p.position_name IS NOT NULL AND p.position_name != ''
                GROUP BY p.position_name
                ORDER BY count DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getContractStats() {
        $stmt = $this->pdo->query("SELECT status, COUNT(*) AS count FROM contracts GROUP BY status");
        return $stmt->fetchAll();
    }

    public function getExpiringContracts($days = 90) {
        $sql = "SELECT c.*,
                       c.contract_id AS id,
                       CONCAT('HD-', LPAD(c.contract_id, 4, '0')) AS contract_number,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE c.status = 'Active'
                AND c.end_date IS NOT NULL
                AND c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY c.end_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getExpiredContracts() {
        $sql = "SELECT c.*,
                       c.contract_id AS id,
                       CONCAT('HD-', LPAD(c.contract_id, 4, '0')) AS contract_number,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE c.end_date IS NOT NULL
                AND c.end_date < CURDATE()
                ORDER BY c.end_date DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getSalaryReport($month, $year) {
        $sql = "SELECT s.*,
                       s.salary_id AS id,
                       s.salary_month AS month,
                       s.salary_year AS year,
                       " . $this->salaryBaseExpression('s') . " AS base_salary,
                       s.allowance AS allowances,
                       s.deduction AS deductions,
                       s.total_salary AS net_salary,
                       s.calculated_date AS payment_date,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position,
                       d.department_name AS department
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE s.salary_month = ? AND s.salary_year = ?
                ORDER BY e.full_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }

    public function getSalarySummary($month, $year) {
        $sql = "SELECT
                    COUNT(DISTINCT s.employee_id) AS total_employees,
                    SUM(" . $this->salaryBaseExpression('s') . ") AS total_base_salary,
                    SUM(s.allowance) AS total_allowances,
                    SUM(s.deduction) AS total_deductions,
                    SUM(s.insurance) AS total_insurance,
                    SUM(s.total_salary) AS total_net_salary,
                    AVG(s.total_salary) AS avg_net_salary
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                WHERE s.salary_month = ? AND s.salary_year = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetch();
    }

    public function getSalaryByDepartment($month, $year) {
        $sql = "SELECT d.department_name AS department,
                       COUNT(DISTINCT s.employee_id) AS total_employees,
                       SUM(" . $this->salaryBaseExpression('s') . ") AS total_base_salary,
                       SUM(s.total_salary) AS total_net_salary,
                       AVG(s.total_salary) AS avg_net_salary
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE s.salary_month = ? AND s.salary_year = ?
                AND d.department_name IS NOT NULL AND d.department_name != ''
                GROUP BY d.department_name
                ORDER BY total_net_salary DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }

    public function getSalaryHistory($employee_id, $months = 12) {
        $sql = "SELECT *, salary_id AS id, salary_month AS month, salary_year AS year,
                       allowance AS allowances, deduction AS deductions,
                       total_salary AS net_salary, calculated_date AS payment_date
                FROM salaries
                WHERE employee_id = ?
                ORDER BY salary_year DESC, salary_month DESC
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id, $months]);
        return $stmt->fetchAll();
    }

    public function getEmployeesNeedingContract($days = 30) {
        $sql = "SELECT e.*,
                       e.employee_id AS id,
                       d.department_name AS department,
                       p.position_name AS position,
                       (SELECT MAX(end_date) FROM contracts WHERE employee_id = e.employee_id) AS last_contract_end
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM contracts c
                    WHERE c.employee_id = e.employee_id AND c.status = 'Active'
                )
                OR (
                    SELECT MAX(end_date) FROM contracts c
                    WHERE c.employee_id = e.employee_id AND c.status = 'Active'
                ) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY e.hire_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getNewEmployees() {
        $sql = "SELECT e.*, e.employee_id AS id, d.department_name AS department, p.position_name AS position
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                ORDER BY e.hire_date DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getYearsOfServiceStats() {
        $sql = "SELECT
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) < 1 THEN '< 1 năm'
                        WHEN TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) BETWEEN 1 AND 3 THEN '1-3 năm'
                        WHEN TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) BETWEEN 4 AND 7 THEN '4-7 năm'
                        ELSE '>= 8 năm'
                    END AS service_range,
                    COUNT(*) AS count
                FROM employees
                WHERE hire_date IS NOT NULL
                GROUP BY service_range
                ORDER BY FIELD(service_range, '< 1 năm', '1-3 năm', '4-7 năm', '>= 8 năm')";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getRewardsAndDisciplines() {
        $sql = "SELECT rp.*,
                       rp.rp_id AS id,
                       rp.created_date AS date_issued,
                       e.full_name,
                       e.employee_code
                FROM rewards_penalties rp
                JOIN employees e ON rp.employee_id = e.employee_id
                ORDER BY rp.created_date DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getRewardsDisciplinesStats() {
        $sql = "SELECT type, COUNT(*) AS count, SUM(amount) AS total_amount
                FROM rewards_penalties
                GROUP BY type";
        return $this->pdo->query($sql)->fetchAll();
    }
}

?>
