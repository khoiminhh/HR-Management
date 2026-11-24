<?php
/**
 * ========================================
 * Controller: PartTimerController
 * ========================================
 */

class PartTimerController {
    private $pdo;
    private $partTimerModel;
    private $employeeModel;

    public function __construct($pdo) {
        $this->pdo            = $pdo;
        $this->partTimerModel = new PartTimer($pdo);
        $this->employeeModel  = new Employee($pdo);
    }

    // ----------------------------------------
    // Danh sách part-timer
    // ----------------------------------------
    public function index() {
        $partTimers = $this->partTimerModel->getAll();
        $message    = $_SESSION['pt_message'] ?? '';
        $errors     = $_SESSION['pt_errors']  ?? [];
        unset($_SESSION['pt_message'], $_SESSION['pt_errors']);

        require '../src/views/part-timers.php';
    }

    // ----------------------------------------
    // Thêm part-timer
    // ----------------------------------------
    public function add() {
        $employees = $this->getEligiblePartTimeEmployees();
        $errors    = [];
        $message   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            // Validate
            if (empty($_POST['employee_id']))       $errors[] = 'Vui lòng chọn nhân viên.';
            if (empty($_POST['hourly_rate']))        $errors[] = 'Vui lòng nhập lương theo giờ.';
            if (empty($_POST['start_date']))         $errors[] = 'Vui lòng nhập ngày bắt đầu.';
            if (empty($_POST['max_hours_per_week'])) $errors[] = 'Vui lòng nhập số giờ tối đa/tuần.';

            if (empty($errors)) {
                if (!$this->isPartTimeEmployee((int)$_POST['employee_id'])) {
                    $errors[] = 'Chi nhan vien co chuc vu Part-time moi duoc them vao danh sach part-time.';
                }
            }

            if (empty($errors)) {
                // Kiểm tra nhân viên đã là part-timer chưa
                if ($this->partTimerModel->checkPartTimer($_POST['employee_id'])) {
                    $errors[] = 'Nhân viên này đã là part-timer đang hoạt động!';
                } else {
                    $data = [
                        'employee_id'        => $_POST['employee_id'],
                        'hourly_rate'        => $_POST['hourly_rate'],
                        'max_hours_per_week' => $_POST['max_hours_per_week'],
                        'start_date'         => $_POST['start_date'],
                        'end_date'           => $_POST['end_date'] ?? null,
                        'contract_type'      => $_POST['contract_type'] ?? 'Part-time',
                        'status'             => $_POST['status'] ?? 'Active',
                    ];

                    if ($this->partTimerModel->create($data)) {
                        $_SESSION['pt_message'] = '✅ Thêm part-timer thành công!';
                        header('Location: ?page=part-timers');
                        exit;
                    } else {
                        $errors[] = 'Lỗi khi lưu dữ liệu, vui lòng thử lại.';
                    }
                }
            }
        }

        require '../src/views/part-timer-form.php';
    }

    private function getEligiblePartTimeEmployees() {
        return array_values(array_filter(
            $this->employeeModel->getAll(),
            fn($employee) => $this->isPartTimeEmployeeRow($employee)
                && !$this->partTimerModel->checkPartTimer($employee['id'] ?? $employee['employee_id'] ?? 0)
        ));
    }

    private function isPartTimeEmployee(int $employeeId): bool {
        $employee = $this->employeeModel->getById($employeeId);
        return $this->isPartTimeEmployeeRow($employee ?: []);
    }

    private function isPartTimeEmployeeRow(array $employee): bool {
        $position = trim((string)($employee['position'] ?? $employee['position_name'] ?? ''));
        return strcasecmp($position, 'Part-time') === 0 || strcasecmp($position, 'Part time') === 0;
    }

    // ----------------------------------------
    // Sửa part-timer
    // ----------------------------------------
    public function edit() {
        $id        = $_GET['id'] ?? null;
        $errors    = [];
        $message   = '';

        if (!$id) {
            header('Location: ?page=part-timers');
            exit;
        }

        $partTimer = $this->partTimerModel->getById($id);
        if (!$partTimer) {
            header('Location: ?page=part-timers');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
            if (empty($_POST['hourly_rate']))        $errors[] = 'Vui lòng nhập lương theo giờ.';
            if (empty($_POST['max_hours_per_week'])) $errors[] = 'Vui lòng nhập số giờ tối đa/tuần.';
            if (empty($_POST['status']))             $errors[] = 'Vui lòng chọn trạng thái.';

            if (empty($errors)) {
                $data = [
                    'hourly_rate'        => $_POST['hourly_rate'],
                    'max_hours_per_week' => $_POST['max_hours_per_week'],
                    'end_date'           => $_POST['end_date'] ?? null,
                    'status'             => $_POST['status'],
                ];

                if ($this->partTimerModel->update($id, $data)) {
                    $_SESSION['pt_message'] = '✅ Cập nhật part-timer thành công!';
                    header('Location: ?page=part-timers');
                    exit;
                } else {
                    $errors[] = 'Lỗi khi cập nhật, vui lòng thử lại.';
                }
            }
        }

        require '../src/views/part-timer-form.php';
    }

    // ----------------------------------------
    // Xóa part-timer
    // ----------------------------------------
    public function delete() {
        $deleteId = $_POST['id'] ?? null;
        if ($deleteId) {
            try {
                $this->partTimerModel->delete($deleteId);
                $_SESSION['pt_message'] = '✅ Xóa part-timer thành công!';
            } catch (Exception $e) {
                $_SESSION['pt_errors'] = ['Lỗi khi xóa: ' . $e->getMessage()];
            }
        }
        header('Location: ?page=part-timers');
        exit;
    }

    // ----------------------------------------
    // Xem giờ công
    // ----------------------------------------
    public function hours() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ?page=part-timers');
            exit;
        }

        $partTimer = $this->partTimerModel->getById($id);
        if (!$partTimer) {
            header('Location: ?page=part-timers');
            exit;
        }

        $month     = $_GET['month'] ?? date('m');
        $year      = $_GET['year']  ?? date('Y');
        $workHours = $this->partTimerModel->getWorkHours($id, $month, $year);
        $summary   = $this->partTimerModel->calculateTotalSalary($id, $month, $year);

        // Xử lý thêm giờ công
        $message = '';
        $errors  = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_hours') {
            $hoursData = [
                'part_timer_id' => $id,
                'work_date'     => $_POST['work_date'],
                'hours'         => $_POST['hours'],
                'hourly_rate'   => $partTimer['hourly_rate'],
                'notes'         => $_POST['notes'] ?? '',
            ];
            $errors = $this->partTimerModel->validateWorkHours($hoursData);
            if (empty($errors) && $this->partTimerModel->addWorkHours($hoursData)) {
                $message   = '✅ Thêm giờ công thành công!';
                $month     = (int)date('m', strtotime($hoursData['work_date']));
                $year      = (int)date('Y', strtotime($hoursData['work_date']));
                $workHours = $this->partTimerModel->getWorkHours($id, $month, $year);
                $summary   = $this->partTimerModel->calculateTotalSalary($id, $month, $year);
            } elseif (empty($errors)) {
                $errors[] = 'Lỗi khi thêm giờ công.';
            }
        }

        require '../src/views/part-time-hours-view.php';
    }
}
?>
