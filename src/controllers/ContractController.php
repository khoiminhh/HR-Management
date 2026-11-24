<?php
/**
 * ========================================
 * Controller: Contract
 * ========================================
 */

require_once '../src/models/Contract.php';
require_once '../src/models/Employee.php';

class ContractController {
    private $contract;
    private $employee;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->contract = new Contract($pdo);
        $this->employee = new Employee($pdo);
    }
    
    /**
     * Hiển thị danh sách hợp đồng
     */
    public function index() {
        $contracts = $this->contract->getAll();
        return $contracts;
    }
    
    /**
     * Hiển thị form thêm hợp đồng
     */
    public function showAddForm() {
        $employees = $this->employee->getAll();
        $contractTypes = ['Official', 'Internship', 'Part-time'];
        
        return [
            'form' => 'add',
            'employees' => $employees,
            'contractTypes' => $contractTypes
        ];
    }
    
    /**
     * Lưu hợp đồng mới
     */
    public function store($data) {
        $errors = [];
        
        // Validation
        if (empty($data['employee_id'])) {
            $errors[] = 'Nhân viên không được để trống';
        }
        
        if (empty($data['contract_type'])) {
            $errors[] = 'Loại hợp đồng không được để trống';
        }
        
        if (empty($data['start_date'])) {
            $errors[] = 'Ngày bắt đầu không được để trống';
        }
        
        if (!empty($data['end_date']) && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            $errors[] = 'Ngày kết thúc phải sau ngày bắt đầu';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Thêm hợp đồng
        try {
            $this->contract->create($data);
            return [
                'success' => true,
                'message' => 'Thêm hợp đồng thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Hiển thị form sửa hợp đồng
     */
    public function showEditForm($id) {
        $contract = $this->contract->getById($id);
        $employees = $this->employee->getAll();
        $contractTypes = ['Official', 'Internship', 'Part-time'];
        
        if (!$contract) {
            return [
                'success' => false,
                'message' => 'Hợp đồng không tồn tại'
            ];
        }
        
        return [
            'form' => 'edit',
            'contract' => $contract,
            'employees' => $employees,
            'contractTypes' => $contractTypes
        ];
    }
    
    /**
     * Cập nhật hợp đồng
     */
    public function update($id, $data) {
        $errors = [];
        
        // Kiểm tra hợp đồng tồn tại
        $contract = $this->contract->getById($id);
        if (!$contract) {
            return [
                'success' => false,
                'errors' => ['Hợp đồng không tồn tại']
            ];
        }
        
        // Validation
        if (empty($data['employee_id'])) {
            $errors[] = 'Nhân viên không được để trống';
        }
        
        if (empty($data['contract_type'])) {
            $errors[] = 'Loại hợp đồng không được để trống';
        }
        
        if (empty($data['start_date'])) {
            $errors[] = 'Ngày bắt đầu không được để trống';
        }
        
        if (!empty($data['end_date']) && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            $errors[] = 'Ngày kết thúc phải sau ngày bắt đầu';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Cập nhật hợp đồng
        try {
            $this->contract->update($id, $data);
            return [
                'success' => true,
                'message' => 'Cập nhật hợp đồng thành công!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Lỗi: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Xóa hợp đồng
     */
    public function delete($id) {
        $contract = $this->contract->getById($id);
        if (!$contract) {
            return [
                'success' => false,
                'message' => 'Hợp đồng không tồn tại'
            ];
        }
        
        try {
            $this->contract->delete($id);
            return [
                'success' => true,
                'message' => 'Xóa hợp đồng thành công!'
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
