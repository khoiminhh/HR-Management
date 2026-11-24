<?php
/**
 * ========================================
 * Entry Point: index.php
 * Hệ thống Quản lý Nhân sự
 * ========================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../src/helpers/Auth.php';

Auth::requireLogin();

$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? '';

switch ($page) {

    case 'dashboard':
        require '../src/views/dashboard-simple.php';
        break;

    case 'employees':
        Auth::requireRole(['Admin', 'HR']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/employee-form.php';
        } else {
            require '../src/views/employees.php';
        }
        break;

    case 'contracts':
        Auth::requireRole(['Admin', 'HR']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/contract-form.php';
        } else {
            require '../src/views/contracts.php';
        }
        break;

    case 'salaries':
        Auth::requireRole(['Admin', 'HR', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/salary-form.php';
        } else {
            require '../src/views/salaries.php';
        }
        break;

    case 'shifts':
        Auth::requireRole(['Admin', 'HR', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/shift-form.php';
        } else {
            require '../src/views/shifts.php';
        }
        break;

    case 'schedules':
        Auth::requireRole(['Admin', 'HR', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/schedule-form.php';
        } else {
            require '../src/views/schedules.php';
        }
        break;

    case 'attendance-requests':
        Auth::requireRole(['Admin', 'HR']);
        require '../src/views/attendance-requests.php';
        break;

    case 'part-timers':
        Auth::requireRole(['Admin', 'HR']);
        require_once '../src/models/PartTimer.php';
        require_once '../src/models/Employee.php';
        require_once '../src/controllers/PartTimerController.php';

        $controller = new PartTimerController($pdo);

        if ($action === 'add') {
            $controller->add();
        } elseif ($action === 'edit') {
            $controller->edit();
        } elseif ($action === 'hours') {
            $controller->hours();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
            $controller->delete();
        } else {
            $controller->index();
        }
        break;
        

    case 'departments':
        Auth::requireRole(['Admin', 'HR']);
        require_once '../src/controllers/DepartmentController.php';
        $deptController = new DepartmentController($pdo);
        if ($action === 'save') {
            $ok = $deptController->save($_POST);
            header('Location: ?page=departments&status=' . ($ok ? 'saved' : 'error'));
            exit;
        } elseif ($action === 'save-position' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ok = $deptController->savePosition($_POST);
            header('Location: ?page=departments&status=' . ($ok ? 'position_saved' : 'error'));
            exit;
        } elseif ($action === 'delete-position' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ok = $deptController->deletePosition($_POST['position_id'] ?? 0);
            header('Location: ?page=departments&status=' . ($ok ? 'position_deleted' : 'error'));
            exit;
        } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ok = $deptController->delete($_POST['id'] ?? 0);
            header('Location: ?page=departments&status=' . ($ok ? 'deleted' : 'error'));
            exit;
        } else {
            include '../src/views/department_form.php';
        }
        break;

    case 'reports':
        Auth::requireRole(['Admin', 'HR', 'Manager']);
        require '../src/views/reports.php';
        break;

    case 'users':
        Auth::requireRole(['Admin']);
        if ($action === 'add' || $action === 'edit') {
            require '../src/views/user-form.php';
        } else {
            require '../src/views/users.php';
        }
        break;
         case 'salary-stats':
        Auth::requireRole(['Admin', 'HR', 'Manager']);
        require_once '../src/controllers/SalaryStatsController.php';
        $controller = new SalaryStatsController($pdo);
        $controller->index();
        break;
    case 'my-pay-and-attendance':
        // Nhân viên chỉ xem lương & giờ công của chính mình
        // Admin/HR/Manager vẫn có thể truy cập, nhưng controller vẫn lọc theo employee_id của user hiện tại
        Auth::requireRole(['Admin','HR','Manager','Employee']);
        require_once '../src/controllers/MyPayAttendanceController.php';
        $controller = new MyPayAttendanceController($pdo);
        $controller->index();
        break;

    default:
        require '../src/views/dashboard-simple.php';
        break;
}
?>
