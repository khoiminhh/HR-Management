<?php
/**
 * ========================================
 * Controller: SalaryStatsController
 * ========================================
 */

class SalaryStatsController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        $filter = $_GET['filter'] ?? 'month';
        $date   = $_GET['date'] ?? date('Y-m-d');
        $month  = $_GET['month'] ?? date('m');
        $year   = $_GET['year'] ?? date('Y');
        $week   = $_GET['week'] ?? date('W');

        $fulltime = $this->getFulltimeStats($filter, $date, $month, $year, $week);
        $parttime = $this->getParttimeStats($filter, $date, $month, $year, $week);
        $chartData = $this->getChartData($filter, $month, $year, $week, $date);

        require '../src/views/salary-stats.php';
    }

    private function salarySelect($where, $group = '') {
        $baseSalary = $this->salaryBaseExpression('s');

        if ($group) {
            return "SELECT e.full_name,
                           e.employee_code,
                           d.department_name AS department,
                           SUM($baseSalary) AS base_salary,
                           SUM(s.allowance) AS allowances,
                           SUM(s.deduction) AS deductions,
                           SUM(s.insurance) AS insurance,
                           SUM(s.total_salary) AS net_salary,
                           COUNT(*) AS months_count
                    FROM salaries s
                    JOIN employees e ON s.employee_id = e.employee_id
                    LEFT JOIN departments d ON e.department_id = d.department_id
                    $where
                    $group
                    ORDER BY net_salary DESC";
        }

        return "SELECT s.*,
                       s.salary_id AS id,
                       s.salary_month AS month,
                       s.salary_year AS year,
                       $baseSalary AS base_salary,
                       s.allowance AS allowances,
                       s.deduction AS deductions,
                       s.total_salary AS net_salary,
                       s.calculated_date AS payment_date,
                       e.full_name,
                       e.employee_code,
                       d.department_name AS department
                FROM salaries s
                JOIN employees e ON s.employee_id = e.employee_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                $where
                ORDER BY s.total_salary DESC";
    }

    private function salaryBaseExpression($alias = 's') {
        return "COALESCE($alias.total_salary, 0) - COALESCE($alias.allowance, 0) + COALESCE($alias.deduction, 0) + COALESCE($alias.insurance, 0)";
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

    private function getFulltimeStats($filter, $date, $month, $year, $week) {
        switch ($filter) {
            case 'day':
                $sql = $this->salarySelect("WHERE s.calculated_date = ?");
                $params = [$date];
                break;
            case 'week':
                $sql = $this->salarySelect("WHERE YEAR(s.calculated_date) = ? AND WEEK(s.calculated_date, 1) = ?");
                $params = [$year, $week];
                break;
            case 'year':
                $sql = $this->salarySelect(
                    "WHERE s.salary_year = ?",
                    "GROUP BY s.employee_id, e.full_name, e.employee_code, d.department_name"
                );
                $params = [$year];
                break;
            default:
                $sql = $this->salarySelect("WHERE s.salary_month = ? AND s.salary_year = ?");
                $params = [$month, $year];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'summary' => [
                'total_base' => array_sum(array_column($rows, 'base_salary')),
                'total_allowances' => array_sum(array_column($rows, 'allowances')),
                'total_deductions' => array_sum(array_column($rows, 'deductions')),
                'total_insurance' => array_sum(array_column($rows, 'insurance')),
                'total_net' => array_sum(array_column($rows, 'net_salary')),
                'count' => count($rows),
            ],
        ];
    }

    private function emptyParttimeStats() {
        return [
            'rows' => [],
            'summary' => [
                'total_hours' => 0,
                'total_amount' => 0,
                'count' => 0,
            ],
        ];
    }

    private function getParttimeStats($filter, $date, $month, $year, $week) {
        if (!$this->tableExists('part_time_hours') || !$this->tableExists('part_timers')) {
            return $this->emptyParttimeStats();
        }

        $select = "SELECT pt.id,
                          e.full_name,
                          e.employee_code,
                          d.department_name AS department,
                          SUM(pth.hours) AS total_hours,
                          AVG(pth.hourly_rate) AS hourly_rate,
                          SUM(pth.amount) AS total_amount,
                          COUNT(DISTINCT pth.work_date) AS work_days,
                          COUNT(DISTINCT DATE_FORMAT(pth.work_date, '%Y-%m')) AS months_active";

        $from = " FROM part_time_hours pth
                  JOIN part_timers pt ON pth.part_timer_id = pt.id
                  JOIN employees e ON pt.employee_id = e.employee_id
                  LEFT JOIN departments d ON e.department_id = d.department_id";

        $group = " GROUP BY pt.id, e.full_name, e.employee_code, d.department_name
                   ORDER BY total_amount DESC";

        switch ($filter) {
            case 'day':
                $where = " WHERE pth.work_date = ?";
                $params = [$date];
                break;
            case 'week':
                $where = " WHERE YEAR(pth.work_date) = ? AND WEEK(pth.work_date, 1) = ?";
                $params = [$year, $week];
                break;
            case 'year':
                $where = " WHERE YEAR(pth.work_date) = ?";
                $params = [$year];
                break;
            default:
                $where = " WHERE MONTH(pth.work_date) = ? AND YEAR(pth.work_date) = ?";
                $params = [$month, $year];
        }

        $stmt = $this->pdo->prepare($select . $from . $where . $group);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'summary' => [
                'total_hours' => array_sum(array_column($rows, 'total_hours')),
                'total_amount' => array_sum(array_column($rows, 'total_amount')),
                'count' => count($rows),
            ],
        ];
    }

    private function getChartData($filter, $month, $year, $week, $date) {
        $labels = [];
        $ftData = [];
        $ptData = [];

        if ($filter === 'year') {
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = "T$m";
                $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(total_salary), 0) FROM salaries WHERE salary_month = ? AND salary_year = ?");
                $stmt->execute([$m, $year]);
                $ftData[] = (float)$stmt->fetchColumn();
                $ptData[] = $this->sumParttimeAmount("MONTH(work_date) = ? AND YEAR(work_date) = ?", [$m, $year]);
            }
        } elseif ($filter === 'week') {
            $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $ftData = array_fill(0, 7, 0);
            $ptData = array_fill(0, 7, 0);
            $stmt = $this->pdo->prepare("SELECT DAYOFWEEK(calculated_date) AS dow, SUM(total_salary) AS total
                                          FROM salaries
                                          WHERE calculated_date IS NOT NULL
                                          AND YEAR(calculated_date) = ? AND WEEK(calculated_date, 1) = ?
                                          GROUP BY dow");
            $stmt->execute([$year, $week]);
            $dowMap = array_fill(1, 7, 0);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $dowMap[(int)$row['dow']] = (float)$row['total'];
            }
            $ftData = [$dowMap[2], $dowMap[3], $dowMap[4], $dowMap[5], $dowMap[6], $dowMap[7], $dowMap[1]];
            if ($this->tableExists('part_time_hours')) {
                $stmt = $this->pdo->prepare("SELECT DAYOFWEEK(work_date) AS dow, SUM(amount) AS total
                                              FROM part_time_hours
                                              WHERE YEAR(work_date) = ? AND WEEK(work_date, 1) = ?
                                              GROUP BY dow");
                $stmt->execute([$year, $week]);
                $dowMap = array_fill(1, 7, 0);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $dowMap[(int)$row['dow']] = (float)$row['total'];
                }
                $ptData = [$dowMap[2], $dowMap[3], $dowMap[4], $dowMap[5], $dowMap[6], $dowMap[7], $dowMap[1]];
            }
        } elseif ($filter === 'day') {
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("$date -$i days"));
                $labels[] = date('d/m', strtotime($d));
                $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(total_salary), 0) FROM salaries WHERE calculated_date = ?");
                $stmt->execute([$d]);
                $ftData[] = (float)$stmt->fetchColumn();
                $ptData[] = $this->sumParttimeAmount("work_date = ?", [$d]);
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $m = date('m', strtotime("$year-$month-01 -$i months"));
                $y = date('Y', strtotime("$year-$month-01 -$i months"));
                $labels[] = "T$m/$y";
                $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(total_salary), 0) FROM salaries WHERE salary_month = ? AND salary_year = ?");
                $stmt->execute([$m, $y]);
                $ftData[] = (float)$stmt->fetchColumn();
                $ptData[] = $this->sumParttimeAmount("MONTH(work_date) = ? AND YEAR(work_date) = ?", [$m, $y]);
            }
        }

        return [
            'labels' => $labels,
            'ftData' => $ftData,
            'ptData' => $ptData,
        ];
    }

    private function sumParttimeAmount($where, $params) {
        if (!$this->tableExists('part_time_hours')) {
            return 0.0;
        }

        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM part_time_hours WHERE $where");
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }
}

?>
