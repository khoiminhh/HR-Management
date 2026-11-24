<?php
/**
 * ========================================
 * Controller: Salary
 * ========================================
 */

require_once '../src/models/Salary.php';
require_once '../src/models/Employee.php';

class SalaryController {
    private $salary;
    private $employee;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->salary = new Salary($pdo);
        $this->employee = new Employee($pdo);
    }
    
    /**
     * Hiển thị danh sách bảng lương
     */
    public function index() {
        $salaries = $this->salary->getAll();
        return $salaries;
    }
    
    /**
     * Hiển thị form thêm bảng lương
     */
    public function showAddForm() {
        $employees = $this->employee->getAll();
        $employees = $this->withActiveContracts($employees);
        
        return [
            'form' => 'add',
            'employees' => $employees,
            'currentMonth' => (int)date('m'),
            'currentYear' => (int)date('Y')
        ];
    }
    
    /**
     * Lưu bảng lương mới
     */
    public function store($data) {
        $errors = [];
        $data = $this->normalizeSalaryData($data);
        $data = $this->applyContractSalary($data, $errors);
        
        // Validation
        if (empty($data['employee_id'])) {
            $errors[] = 'Nhân viên không được để trống';
        }
        
        if (empty($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            $errors[] = 'Tháng không hợp lệ';
        }
        
        if (empty($data['year'])) {
            $errors[] = 'Năm không được để trống';
        }
        
        if ($data['base_salary'] <= 0) {
            $errors[] = 'Lương cơ bản phải lớn hơn 0';
        }

        if ($data['allowances'] < 0 || $data['deductions'] < 0 || $data['insurance'] < 0) {
            $errors[] = 'Phụ cấp, khấu trừ và bảo hiểm không được âm';
        }

        if ($this->calculateNetSalary($data) < 0) {
            $errors[] = 'Lương thực lĩnh không được âm. Vui lòng kiểm tra khấu trừ/bảo hiểm.';
        }
        
        if ($this->salary->checkExists($data['employee_id'], $data['month'], $data['year'])) {
            $errors[] = 'Bảng lương cho tháng/năm này đã tồn tại';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Thêm bảng lương
        try {
            $this->salary->create($data);
            return [
                'success' => true,
                'message' => 'Thêm bảng lương thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Hiển thị form sửa bảng lương
     */
    public function showEditForm($id) {
        $salary = $this->salary->getById($id);
        $employees = $this->employee->getAll();
        $employees = $this->withActiveContracts($employees);
        
        if (!$salary) {
            return [
                'success' => false,
                'message' => 'Bảng lương không tồn tại'
            ];
        }
        
        return [
            'form' => 'edit',
            'salary' => $salary,
            'employees' => $employees
        ];
    }
    
    /**
     * Cập nhật bảng lương
     */
    public function update($id, $data) {
        $errors = [];
        $data = $this->normalizeSalaryData($data);
        $data = $this->applyContractSalary($data, $errors);
        
        // Kiểm tra bảng lương tồn tại
        $salary = $this->salary->getById($id);
        if (!$salary) {
            return [
                'success' => false,
                'errors' => ['Bảng lương không tồn tại']
            ];
        }
        
        // Validation
        if (empty($data['employee_id'])) {
            $errors[] = 'Nhân viên không được để trống';
        }
        
        if (empty($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            $errors[] = 'Tháng không hợp lệ';
        }
        
        if (empty($data['year'])) {
            $errors[] = 'Năm không được để trống';
        }
        
        if ($data['base_salary'] <= 0) {
            $errors[] = 'Lương cơ bản phải lớn hơn 0';
        }

        if ($data['allowances'] < 0 || $data['deductions'] < 0 || $data['insurance'] < 0) {
            $errors[] = 'Phụ cấp, khấu trừ và bảo hiểm không được âm';
        }

        if ($this->calculateNetSalary($data) < 0) {
            $errors[] = 'Lương thực lĩnh không được âm. Vui lòng kiểm tra khấu trừ/bảo hiểm.';
        }
        
        if ($this->salary->checkExists($data['employee_id'], $data['month'], $data['year'], $id)) {
            $errors[] = 'Bảng lương cho tháng/năm này đã tồn tại';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Cập nhật bảng lương
        try {
            $this->salary->update($id, $data);
            return [
                'success' => true,
                'message' => 'Cập nhật bảng lương thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Xóa bảng lương
     */
    public function delete($id) {
        $salary = $this->salary->getById($id);
        if (!$salary) {
            return [
                'success' => false,
                'message' => 'Bảng lương không tồn tại'
            ];
        }
        
        try {
            $this->salary->delete($id);
            return [
                'success' => true,
                'message' => 'Xóa bảng lương thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    private function normalizeSalaryData($data) {
        $data['base_salary'] = Salary::toMoney($data['base_salary'] ?? 0);
        $data['allowances'] = Salary::toMoney($data['allowances'] ?? 0);
        $data['deductions'] = Salary::toMoney($data['deductions'] ?? 0);
        $data['insurance'] = Salary::toMoney($data['insurance'] ?? 0);
        $data['month'] = (int)($data['month'] ?? 0);
        $data['year'] = (int)($data['year'] ?? 0);

        return $data;
    }

    private function applyContractSalary($data, &$errors) {
        if (empty($data['employee_id']) || empty($data['month']) || empty($data['year'])) {
            return $data;
        }

        $contractSalary = $this->salary->getActiveContractSalary($data['employee_id'], $data['month'], $data['year']);

        if ($contractSalary === null || $contractSalary <= 0) {
            $errors[] = 'Nhan vien chua co hop dong dang hieu luc trong ky luong nay';
            $data['base_salary'] = 0;
            return $data;
        }

        $data['base_salary'] = $contractSalary;

        return $data;
    }

    private function withActiveContracts($employees) {
        $contractsByEmployee = $this->salary->getActiveContractsByEmployee();

        foreach ($employees as &$employee) {
            $employeeId = (string)($employee['id'] ?? $employee['employee_id'] ?? '');
            $employee['active_contracts'] = $contractsByEmployee[$employeeId] ?? [];
        }

        unset($employee);

        return $employees;
    }

    private function calculateNetSalary($data) {
        return $data['base_salary'] + $data['allowances'] - $data['deductions'] - $data['insurance'];
    }
}

?>
