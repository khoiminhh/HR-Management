<?php
/**
 * ========================================
 * Model: Contract (Hợp đồng)
 * ========================================
 */

class Contract {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Lấy tất cả hợp đồng
     */
    public function getAll() {
        $sql = "SELECT c.*,
                       c.contract_id AS id,
                       CONCAT('HD-', LPAD(c.contract_id, 4, '0')) AS contract_number,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position,
                       d.department_name AS department
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                ORDER BY c.contract_id DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Đếm tổng số hợp đồng
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM contracts";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Lấy hợp đồng theo ID
     */
    public function getById($id) {
        $sql = "SELECT c.*,
                       c.contract_id AS id,
                       CONCAT('HD-', LPAD(c.contract_id, 4, '0')) AS contract_number,
                       e.full_name,
                       e.employee_code,
                       p.position_name AS position,
                       d.department_name AS department,
                       e.email,
                       e.phone
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                WHERE c.contract_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Lấy hợp đồng theo nhân viên
     */
    public function getByEmployeeId($employee_id) {
        $sql = "SELECT *, contract_id AS id, CONCAT('HD-', LPAD(contract_id, 4, '0')) AS contract_number
                FROM contracts 
                WHERE employee_id = ? 
                ORDER BY start_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Thêm hợp đồng mới
     */
    public function create($data) {
        $sql = "INSERT INTO contracts 
                (employee_id, contract_type, start_date, end_date, salary, status)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            $data['employee_id'],
            $data['contract_type'],
            $data['start_date'],
            $data['end_date'] ?: null,
            $data['salary'] ?: null,
            $data['status'] ?? 'Active'
        ]);
    }
    
    /**
     * Cập nhật hợp đồng
     */
    public function update($id, $data) {
        $sql = "UPDATE contracts SET 
                employee_id = ?,
                contract_type = ?,
                start_date = ?,
                end_date = ?,
                salary = ?,
                status = ?
                WHERE contract_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            $data['employee_id'],
            $data['contract_type'],
            $data['start_date'],
            $data['end_date'] ?: null,
            $data['salary'] ?: null,
            $data['status'],
            $id
        ]);
    }
    
    /**
     * Xóa hợp đồng
     */
    public function delete($id) {
        $sql = "DELETE FROM contracts WHERE contract_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Kiểm tra số hợp đồng đã tồn tại
     */
    public function checkContractNumberExists($number, $excludeId = null) {
        return false;
    }
    
    /**
     * Lấy hợp đồng sắp hết hạn
     */
    public function getExpiringSoon($days = 30) {
        $sql = "SELECT c.*, e.full_name, e.employee_code 
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                WHERE c.status = 'Active' 
                AND c.end_date IS NOT NULL
                AND c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY c.end_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy hợp đồng đã hết hạn
     */
    public function getExpired() {
        $sql = "SELECT c.*, e.full_name, e.employee_code 
                FROM contracts c
                JOIN employees e ON c.employee_id = e.employee_id
                WHERE c.status = 'Active' 
                AND c.end_date IS NOT NULL
                AND c.end_date < CURDATE()
                ORDER BY c.end_date DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy danh sách loại hợp đồng
     */
    public function getContractTypes() {
        $sql = "SELECT DISTINCT contract_type FROM contracts 
                WHERE contract_type IS NOT NULL AND contract_type != ''
                ORDER BY contract_type";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}

?>
