<?php
/**
 * ========================================
 * Export Employees to Excel/CSV
 * ========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../src/helpers/Auth.php';
require_once '../src/models/ExcelExport.php';
require_once '../src/models/Employee.php';

// Check login & role
Auth::requireLogin();
Auth::requireRole(['Admin', 'HR']);

// Get employees
$employeeModel = new Employee($pdo);
$employees = $employeeModel->getAll();

// Export to CSV
ExcelExport::exportEmployees($employees);

?>
