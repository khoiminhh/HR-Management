<?php
/**
 * ========================================
 * Export Salaries to Excel/CSV
 * ========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../src/helpers/Auth.php';
require_once '../src/models/ExcelExport.php';
require_once '../src/models/Salary.php';

// Check login & role
Auth::requireLogin();
Auth::requireRole(['Admin', 'HR', 'Manager']);

// Get parameters
$month = $_GET['month'] ?? (int)date('m');
$year = $_GET['year'] ?? (int)date('Y');

// Get salaries
$salaryModel = new Salary($pdo);
$salaries = $salaryModel->getByMonthYear($month, $year);

// Export to CSV
ExcelExport::exportSalaries($salaries, $month, $year);

?>
