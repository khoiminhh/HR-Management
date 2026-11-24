<?php
/**
 * ========================================
 * Export Contracts to Excel/CSV
 * ========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../src/helpers/Auth.php';
require_once '../src/models/ExcelExport.php';
require_once '../src/models/Contract.php';

// Check login & role
Auth::requireLogin();
Auth::requireRole(['Admin', 'HR']);

// Get contracts
$contractModel = new Contract($pdo);
$contracts = $contractModel->getAll();

// Export to CSV
ExcelExport::exportContracts($contracts);

?>
