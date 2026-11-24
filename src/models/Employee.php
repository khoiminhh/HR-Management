<?php
/**
 * ========================================
 * Model: Employee
 * ========================================
 */

class Employee {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all employees
     */
    public function getAll($limit = null, $offset = 0) {

        $sql = "SELECT 
                    e.*,
                    e.employee_id AS id,
                    d.department_name,
                    d.department_name AS department,
                    p.position_name,
                    p.position_name AS position,
                    'Active' AS status
                FROM employees e
                LEFT JOIN departments d
                ON e.department_id = d.department_id
                LEFT JOIN positions p
                ON e.position_id = p.position_id
                ORDER BY e.employee_id DESC";

        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count employees
     */
    public function countAll() {

        $sql = "SELECT COUNT(*) as total FROM employees";

        $stmt = $this->pdo->query($sql);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'];
    }

    /**
     * Get employee by ID
     */
    public function getById($id) {

        $sql = "SELECT 
                    e.*,
                    e.employee_id AS id,
                    d.department_name,
                    d.department_name AS department,
                    p.position_name,
                    p.position_name AS position,
                    'Active' AS status
                FROM employees e
                LEFT JOIN departments d
                ON e.department_id = d.department_id
                LEFT JOIN positions p
                ON e.position_id = p.position_id
                WHERE e.employee_id = ?";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee by code
     */
    public function getByCode($code) {

        $sql = "SELECT *
                FROM employees
                WHERE employee_code = ?";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$code]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create employee
     */
    public function create($data) {

        $sql = "INSERT INTO employees

                (
                    employee_code,
                    full_name,
                    gender,
                    date_of_birth,
                    phone,
                    email,
                    address,
                    hire_date,
                    basic_salary,
                    identity_card,
                    education_level,
                    experience_years,
                    marital_status,
                    department_id,
                    position_id
                )

                VALUES

                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([

            $data['employee_code'] ?? null,
            $data['full_name'] ?? '',
            $data['gender'] ?: null,
            $data['date_of_birth'] ?: null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['hire_date'] ?: null,
            $data['basic_salary'] ?? null,
            $data['identity_card'] ?? null,
            $data['education_level'] ?? null,
            $data['experience_years'] ?? 0,
            $data['marital_status'] ?? null,
            $data['department_id'] ?: null,
            $data['position_id'] ?: null

        ]);
    }

    /**
     * Update employee
     */
    public function update($id, $data) {

        $sql = "UPDATE employees SET

                    employee_code = ?,
                    full_name = ?,
                    gender = ?,
                    date_of_birth = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    hire_date = ?,
                    basic_salary = ?,
                    identity_card = ?,
                    education_level = ?,
                    experience_years = ?,
                    marital_status = ?,
                    department_id = ?,
                    position_id = ?

                WHERE employee_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([

            $data['employee_code'] ?? null,
            $data['full_name'] ?? '',
            $data['gender'] ?: null,
            $data['date_of_birth'] ?: null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['hire_date'] ?: null,
            $data['basic_salary'] ?? null,
            $data['identity_card'] ?? null,
            $data['education_level'] ?? null,
            $data['experience_years'] ?? 0,
            $data['marital_status'] ?? null,
            $data['department_id'] ?: null,
            $data['position_id'] ?: null,
            $id

        ]);
    }

    /**
     * Delete employee
     */
    public function delete($id) {

        $sql = "DELETE FROM employees
                WHERE employee_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }

    /**
     * Check employee code exists
     */
    public function checkCodeExists($code, $excludeId = null) {

        $sql = "SELECT COUNT(*) as count
                FROM employees
                WHERE employee_code = ?";

        if ($excludeId) {

            $sql .= " AND employee_id != ?";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$code, $excludeId]);

        } else {

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$code]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Check email exists
     */
    public function checkEmailExists($email, $excludeId = null) {

        $sql = "SELECT COUNT(*) as count
                FROM employees
                WHERE email = ?";

        if ($excludeId) {

            $sql .= " AND employee_id != ?";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$email, $excludeId]);

        } else {

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$email]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Search employees
     */
    public function search($keyword) {

        $sql = "SELECT *
                FROM employees

                WHERE full_name LIKE ?
                OR employee_code LIKE ?
                OR email LIKE ?
                OR phone LIKE ?

                ORDER BY employee_id DESC";

        $search = "%$keyword%";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $search,
            $search,
            $search,
            $search
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employees by department
     */
    public function getByDepartment($departmentId) {

        $sql = "SELECT *
                FROM employees
                WHERE department_id = ?
                ORDER BY full_name";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$departmentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all departments
     */
    public function getDepartments() {

        $sql = "SELECT *, department_id AS id
                FROM departments
                ORDER BY department_name";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all positions
     */
    public function getPositions() {
        $this->ensurePositionExists('Part-time');

        $sql = "SELECT *, position_id AS id
                FROM positions
                ORDER BY position_name";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositionsByDepartmentMap() {
        $this->ensurePositionExists('Part-time');

        $sql = "SELECT
                    dp.department_id,
                    p.position_id,
                    p.position_name
                FROM department_positions dp
                JOIN positions p
                    ON LOWER(p.position_name) = LOWER(dp.position_name)
                ORDER BY dp.department_id, p.position_name";

        try {
            $stmt = $this->pdo->query($sql);
        } catch (PDOException $e) {
            return [];
        }

        $positionsByDepartment = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $position) {
            $departmentId = (string)$position['department_id'];
            $positionsByDepartment[$departmentId][] = [
                'position_id' => (int)$position['position_id'],
                'position_name' => $position['position_name'],
            ];
        }

        return $positionsByDepartment;
    }

    private function ensurePositionExists($positionName) {
        try {
            $stmt = $this->pdo->prepare("SELECT position_id FROM positions WHERE LOWER(position_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$positionName]);
            if ($stmt->fetchColumn()) {
                return;
            }

            $insert = $this->pdo->prepare("INSERT INTO positions (position_name) VALUES (?)");
            $insert->execute([$positionName]);
        } catch (PDOException $e) {
            // If the positions table is not ready, callers will handle the original query failure.
        }
    }
}

?>
