<?php
/**
 * ========================================
 * Controller: Report
 * ========================================
 */

require_once '../src/models/Report.php';

class ReportController {
    private $report;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->report = new Report($pdo);
    }
    
    /**
     * Lấy dữ liệu dashboard
     */
    public function getDashboard() {
        $employeeStats = $this->report->getEmployeeStatsByStatus();
        $contractStats = $this->report->getContractStats();
        $expiringContracts = $this->report->getExpiringContracts();
        $newEmployees = $this->report->getNewEmployees();
        
        return [
            'employeeStats' => $employeeStats,
            'contractStats' => $contractStats,
            'expiringContracts' => $expiringContracts,
            'newEmployees' => $newEmployees
        ];
    }
    
    /**
     * Lấy báo cáo theo phòng ban
     */
    public function getDepartmentReport() {
        $departmentStats = $this->report->getEmployeeStatsByDepartment();
        $positionStats = $this->report->getEmployeeStatsByPosition();
        
        return [
            'departmentStats' => $departmentStats,
            'positionStats' => $positionStats
        ];
    }
    
    /**
     * Lấy báo cáo hợp đồng
     */
    public function getContractReport() {
        $contractStats = $this->report->getContractStats();
        $expiringContracts = $this->report->getExpiringContracts(90);
        $expiredContracts = $this->report->getExpiredContracts();
        $employeesNeedingContract = $this->report->getEmployeesNeedingContract();
        
        return [
            'contractStats' => $contractStats,
            'expiringContracts' => $expiringContracts,
            'expiredContracts' => $expiredContracts,
            'employeesNeedingContract' => $employeesNeedingContract
        ];
    }
    
    /**
     * Lấy báo cáo lương
     */
    public function getSalaryReport($month = null, $year = null) {
        if (!$month || !$year) {
            $month = (int)date('m');
            $year = (int)date('Y');
        }
        
        $salaryData = $this->report->getSalaryReport($month, $year);
        $salarySummary = $this->report->getSalarySummary($month, $year);
        $salaryByDepartment = $this->report->getSalaryByDepartment($month, $year);
        
        return [
            'salaryData' => $salaryData,
            'salarySummary' => $salarySummary,
            'salaryByDepartment' => $salaryByDepartment,
            'month' => $month,
            'year' => $year
        ];
    }
}

?>