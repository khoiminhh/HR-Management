<?php
/**
 * Controller: MyPayAttendanceController
 * Nhân viên: chỉ xem lương + giờ công của chính mình
 */

require_once '../src/models/User.php';
require_once '../src/models/Employee.php';
require_once '../src/models/Salary.php';
require_once '../src/models/Attendance.php';
require_once '../src/models/AttendanceRequest.php';
require_once '../src/models/EmployeeShift.php';
require_once '../src/models/Contract.php';
require_once '../src/models/PartTimer.php';

class MyPayAttendanceController {


    private $pdo;
    private $user;
    private $employeeModel;
    private $salary;
    private $attendance;
    private $attendanceRequest;
    private $employeeShift;
    private $contract;
    private $partTimer;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->user = new User($pdo);
        $this->employeeModel = new Employee($pdo);
        $this->salary = new Salary($pdo);
        $this->attendance = new Attendance($pdo);
        $this->attendanceRequest = new AttendanceRequest($pdo);
        $this->employeeShift = new EmployeeShift($pdo);
        $this->contract = new Contract($pdo);
        $this->partTimer = new PartTimer($pdo);
    }

    public function index() {
        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        if ($month < 1 || $month > 12 || $year < 2000) {
            $month = (int)date('m');
            $year = (int)date('Y');
        }

        $currentUser = Auth::getCurrentUser();
        $canViewAllEmployees = Auth::hasRole(['Admin', 'HR', 'Manager']);
        $employeeOptions = $canViewAllEmployees ? $this->employeeModel->getAll() : [];
        $sessionEmployeeId = (int)($currentUser['employee_id'] ?? 0);

        // fallback nếu trong session không có employee_id
        if (!$sessionEmployeeId) {
            $u = $this->user->getById((int)($currentUser['id'] ?? 0));
            $sessionEmployeeId = (int)($u['employee_id'] ?? 0);
        }

        if ($canViewAllEmployees) {
            $requestedEmployeeId = (int)($_GET['employee_id'] ?? 0);
            $validEmployeeIds = array_map('intval', array_column($employeeOptions, 'id'));

            if ($requestedEmployeeId && in_array($requestedEmployeeId, $validEmployeeIds, true)) {
                $employeeId = $requestedEmployeeId;
            } elseif ($sessionEmployeeId && in_array($sessionEmployeeId, $validEmployeeIds, true)) {
                $employeeId = $sessionEmployeeId;
            } else {
                $employeeId = (int)($employeeOptions[0]['id'] ?? 0);
            }
        } else {
            $employeeId = $sessionEmployeeId;
        }

        $message = $_SESSION['my_attendance_message'] ?? '';
        $errors = $_SESSION['my_attendance_errors'] ?? [];
        unset($_SESSION['my_attendance_message'], $_SESSION['my_attendance_errors']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$canViewAllEmployees && $employeeId) {
            $postAction = $_POST['action'] ?? '';

            if ($postAction === 'web_check_in') {
                if ($this->attendance->checkIn($employeeId)) {
                    $_SESSION['my_attendance_message'] = 'Đã chấm công vào.';
                } else {
                    $_SESSION['my_attendance_errors'] = ['Bạn đã chấm công vào hôm nay hoặc dữ liệu không hợp lệ.'];
                }
                header('Location: ?page=my-pay-and-attendance');
                exit;
            }

            if ($postAction === 'web_check_out') {
                if ($this->attendance->checkOut($employeeId)) {
                    $_SESSION['my_attendance_message'] = 'Đã chấm công ra.';
                } else {
                    $_SESSION['my_attendance_errors'] = ['Không thể chấm công ra. Hãy chấm công vào trước hoặc kiểm tra đã chấm ra chưa.'];
                }
                header('Location: ?page=my-pay-and-attendance');
                exit;
            }

            if ($postAction === 'request_attendance') {
                $requestDate = $_POST['attendance_date'] ?? '';
                $checkIn = $_POST['requested_check_in'] ?? '';
                $checkOut = $_POST['requested_check_out'] ?? '';
                $reason = trim($_POST['reason'] ?? '');
                $requestErrors = [];

                if (!$requestDate || strtotime($requestDate) === false) {
                    $requestErrors[] = 'Ngày chấm công không hợp lệ.';
                }
                if (!$checkIn || !$checkOut || strtotime($checkOut) <= strtotime($checkIn)) {
                    $requestErrors[] = 'Giờ vào/ra không hợp lệ.';
                }
                if ($reason === '') {
                    $requestErrors[] = 'Vui lòng nhập lý do quên chấm công.';
                }

                if (empty($requestErrors)) {
                    $this->attendanceRequest->create([
                        'employee_id' => $employeeId,
                        'attendance_date' => $requestDate,
                        'requested_check_in' => $checkIn,
                        'requested_check_out' => $checkOut,
                        'reason' => $reason,
                    ]);
                    $_SESSION['my_attendance_message'] = 'Đã gửi yêu cầu chấm công. Vui lòng chờ HR/Admin duyệt.';
                } else {
                    $_SESSION['my_attendance_errors'] = $requestErrors;
                }

                header('Location: ?page=my-pay-and-attendance');
                exit;
            }
        }

        $todayAttendance = $employeeId ? $this->attendance->getByEmployeeAndDate($employeeId, date('Y-m-d')) : null;
        $attendanceRequestRows = $employeeId ? $this->attendanceRequest->getByEmployee($employeeId, 8) : [];

        if (!$employeeId) {
            $employee = [
                'full_name' => Auth::getFullName(),
                'employee_code' => '',
                'date_of_birth' => '',
                'address' => '',
                'position' => '',
                'department' => ''
            ];
            $salaryRows = [];
            $attendanceRows = [];
            $scheduleRows = [];
            $workedShiftRows = [];
            $plannedShiftRows = [];
            $activeShiftRows = [];
            $contractRows = [];
            $partTimerInfo = null;
            $partTimeRows = [];
            $partTimeSummary = ['total_hours' => 0, 'total_amount' => 0];
            $summary = ['total_net' => 0, 'total_hours' => 0, 'total_scheduled_hours' => 0, 'scheduled_days' => 0, 'worked_shift_days' => 0, 'part_time_amount' => 0];
        } else {
            $partTimerInfo = $this->partTimer->getByEmployeeId($employeeId);
            $partTimeRows = [];
            $partTimeSummary = ['total_hours' => 0, 'total_amount' => 0];
            $contractRows = $this->contract->getByEmployeeId($employeeId);

            // Salary (full-time)
            $salaryRowsAll = $this->salary->getByEmployeeId($employeeId);
            $salaryRows = array_values(array_filter($salaryRowsAll, function ($s) use ($month, $year) {
                return (int)$s['month'] === (int)$month && (int)$s['year'] === (int)$year;
            }));

            $totalNet = 0;
            foreach ($salaryRows as $r) {
                $totalNet += (float)($r['net_salary'] ?? 0);
            }

            // Attendance: giờ công theo tháng (tổng + danh sách theo ngày)
            // vì Attendance model hiện chỉ có logTime -> tự query tại controller
            $stmt = $this->pdo->prepare(
                "SELECT
                    attendance_date AS work_date,
                    check_in AS check_in_time,
                    check_out AS check_out_time,
                    status,
                    '' AS notes,
                    COALESCE(working_hours, 0) AS hours_worked
                 FROM attendance
                 WHERE employee_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?
                 ORDER BY attendance_date DESC"
            );
            $stmt->execute([$employeeId, $month, $year]);
            $attendanceRows = $stmt->fetchAll();

            $totalHours = 0;
            foreach ($attendanceRows as $ar) {
                $totalHours += (float)($ar['hours_worked'] ?? 0);
            }

            try {
                $scheduleStmt = $this->pdo->prepare(
                    "SELECT s.*, sh.shift_name, sh.start_time, sh.end_time, sh.break_time
                     FROM schedules s
                     LEFT JOIN shifts sh ON s.shift_id = sh.id
                     WHERE s.employee_id = ? AND MONTH(s.schedule_date) = ? AND YEAR(s.schedule_date) = ?
                     ORDER BY s.schedule_date ASC"
                );
                $scheduleStmt->execute([$employeeId, $month, $year]);
                $scheduleRows = $scheduleStmt->fetchAll();
            } catch (PDOException $e) {
                $scheduleRows = [];
            }

            try {
                $workedShiftStmt = $this->pdo->prepare(
                    "SELECT
                        a.attendance_date AS work_date,
                        NULL AS schedule_id,
                        NULL AS schedule_status,
                        '' AS schedule_notes,
                        '' AS shift_name,
                        NULL AS start_time,
                        NULL AS end_time,
                        0 AS break_time,
                        a.check_in AS check_in_time,
                        a.check_out AS check_out_time,
                        a.status AS attendance_status,
                        '' AS attendance_notes,
                        COALESCE(a.working_hours, 0) AS actual_hours
                     FROM attendance a
                     WHERE a.employee_id = ? AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
                     ORDER BY a.attendance_date DESC"
                );
                $workedShiftStmt->execute([$employeeId, $month, $year]);
                $workedShiftRows = $workedShiftStmt->fetchAll();
            } catch (PDOException $e) {
                $workedShiftRows = [];
            }

            try {
                $plannedShiftStmt = $this->pdo->prepare(
                    "SELECT
                        sh.shift_name,
                        sh.start_time,
                        sh.end_time,
                        sh.break_time,
                        COUNT(*) AS scheduled_days,
                        MIN(s.schedule_date) AS first_date,
                        MAX(s.schedule_date) AS last_date
                     FROM schedules s
                     LEFT JOIN shifts sh ON s.shift_id = sh.id
                     WHERE s.employee_id = ? AND MONTH(s.schedule_date) = ? AND YEAR(s.schedule_date) = ?
                     GROUP BY sh.id, sh.shift_name, sh.start_time, sh.end_time, sh.break_time
                     ORDER BY first_date ASC, sh.start_time ASC"
                );
                $plannedShiftStmt->execute([$employeeId, $month, $year]);
                $plannedShiftRows = $plannedShiftStmt->fetchAll();
            } catch (PDOException $e) {
                $plannedShiftRows = [];
            }

            $totalScheduledHours = 0;
            foreach ($scheduleRows as $row) {
                if (empty($row['start_time']) || empty($row['end_time'])) {
                    continue;
                }

                $start = strtotime($row['schedule_date'] . ' ' . $row['start_time']);
                $end = strtotime($row['schedule_date'] . ' ' . $row['end_time']);
                if ($end <= $start) {
                    $end = strtotime('+1 day', $end);
                }

                $breakHours = ((float)($row['break_time'] ?? 0)) / 60;
                $hours = max(0, (($end - $start) / 3600) - $breakHours);
                $totalScheduledHours += $hours;
            }

            $workedShiftDays = 0;
            foreach ($workedShiftRows as &$row) {
                $plannedHours = 0;
                if (!empty($row['start_time']) && !empty($row['end_time'])) {
                    $start = strtotime($row['work_date'] . ' ' . $row['start_time']);
                    $end = strtotime($row['work_date'] . ' ' . $row['end_time']);
                    if ($end <= $start) {
                        $end = strtotime('+1 day', $end);
                    }

                    $breakHours = ((float)($row['break_time'] ?? 0)) / 60;
                    $plannedHours = max(0, (($end - $start) / 3600) - $breakHours);
                }

                $row['planned_hours'] = $plannedHours;
                if ((float)($row['actual_hours'] ?? 0) > 0 || strtolower((string)($row['attendance_status'] ?? '')) === 'present') {
                    $workedShiftDays++;
                }
            }
            unset($row);

            $activeShiftRows = $this->employeeShift->getByEmployeeId($employeeId);

            if ($partTimerInfo) {
                $partTimeRows = $this->partTimer->getWorkHours((int)$partTimerInfo['id'], $month, $year);
                $partTimeSummary = $this->partTimer->calculateTotalSalary((int)$partTimerInfo['id'], $month, $year);
            }

            $employee = $this->employeeModel->getById($employeeId);
            if (!$employee) {
                $employee = [
                    'full_name' => Auth::getFullName(),
                    'employee_code' => '',
                    'date_of_birth' => '',
                    'address' => '',
                    'position' => '',
                    'department' => ''
                ];
            }

            $summary = [
                'total_net' => $totalNet,
                'total_hours' => $totalHours,
                'total_scheduled_hours' => $totalScheduledHours,
                'scheduled_days' => count($scheduleRows),
                'worked_shift_days' => $workedShiftDays,
                'part_time_amount' => (float)($partTimeSummary['total_amount'] ?? 0)
            ];
        }

        require '../src/views/my-pay-and-attendance.php';
    }
}
?>

