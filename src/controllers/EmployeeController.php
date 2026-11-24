<?php
/**
 * ========================================
 * Controller: Employee
 * ========================================
 */

require_once '../src/models/Employee.php';
require_once '../src/models/Email.php';
require_once '../config/email.php';

class EmployeeController {
    private $employee;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->employee = new Employee($pdo);
    }
    
    /**
     * Hiển thị danh sách nhân viên
     */
    public function index() {
        $employees = $this->employee->getAll();
        return $employees;
    }
    
    /**
     * Hiển thị form thêm nhân viên
     */
   public function showAddForm() {
    // Truy vấn lấy danh sách phòng ban
    $departments = $this->employee->getDepartments();
    $positions = $this->employee->getPositions();
    $positionsByDepartment = $this->employee->getPositionsByDepartmentMap();

    return [
        'departments' => $departments,
        'positions' => $positions,
        'positionsByDepartment' => $positionsByDepartment
    ];
}
    
    /**
     * Lưu nhân viên mới
     */
    public function store($data) {
        $errors = [];
        
        // Validation
        if (empty($data['employee_code'])) {
            $errors[] = 'Mã nhân viên không được để trống';
        } elseif ($this->employee->checkCodeExists($data['employee_code'])) {
            $errors[] = 'Mã nhân viên đã tồn tại';
        }
        
        if (empty($data['full_name'])) {
            $errors[] = 'Họ và tên không được để trống';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        } elseif (!empty($data['email']) && $this->employee->checkEmailExists($data['email'])) {
            $errors[] = 'Email đã tồn tại';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Thêm nhân viên
        try {
            $this->employee->create($data);
             // Gửi email chào mừng (nếu có email)
    if (!empty($data['email'])) {
        // TODO: Sau khi tạo user, gửi email
        // Email::sendWelcomeEmail($data, $username, $password);
    }
    return [
        'success' => true,
        'message' => 'Thêm nhân viên thành công!'
    ];
           } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Hiển thị form sửa nhân viên
     */
    public function showEditForm($id) {
        $employee = $this->employee->getById($id);
        $departments = $this->employee->getDepartments();
        $positions = $this->employee->getPositions();
        $positionsByDepartment = $this->employee->getPositionsByDepartmentMap();
        
        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Nhân viên không tồn tại'
            ];
        }
        
        return [
            'form' => 'edit',
            'employee' => $employee,
            'departments' => $departments,
            'positions' => $positions,
            'positionsByDepartment' => $positionsByDepartment
        ];
    }
    
    /**
     * Cập nhật nhân viên
     */
    public function update($id, $data) {
        $errors = [];
        
        // Kiểm tra nhân viên có tồn tại
        $employee = $this->employee->getById($id);
        if (!$employee) {
            return [
                'success' => false,
                'errors' => ['Nhân viên không tồn tại']
            ];
        }
        
        // Validation
        if (empty($data['employee_code'])) {
            $errors[] = 'Mã nhân viên không được để trống';
        } elseif ($this->employee->checkCodeExists($data['employee_code'], $id)) {
            $errors[] = 'Mã nhân viên đã tồn tại';
        }
        
        if (empty($data['full_name'])) {
            $errors[] = 'Họ và tên không được để trống';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        } elseif (!empty($data['email']) && $this->employee->checkEmailExists($data['email'], $id)) {
            $errors[] = 'Email đã tồn tại';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Cập nhật nhân viên
        try {
            $this->employee->update($id, $data);
            return [
                'success' => true,
                'message' => 'Cập nhật nhân viên thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Xóa nhân viên
     */
    public function delete($id) {
        $employee = $this->employee->getById($id);
        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Nhân viên không tồn tại'
            ];
        }
        
        try {
            $this->employee->delete($id);
            return [
                'success' => true,
                'message' => 'Xóa nhân viên thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }
}

?>
