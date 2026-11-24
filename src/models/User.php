<?php
/**
 * ========================================
 * Model: User
 * ========================================
 */

class User {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all users
     */
    public function getAll() {

                $sql = "SELECT 
                    u.*,
                    u.user_id AS id,
                    e.employee_id,
                    e.employee_code,
                    COALESCE(NULLIF(e.full_name, ''), NULLIF(u.full_name, ''), u.username) AS full_name,
                    COALESCE(NULLIF(e.email, ''), NULLIF(u.email, '')) AS email,
                    COALESCE(NULLIF(e.phone, ''), NULLIF(u.phone, '')) AS phone
                FROM users u
                LEFT JOIN employees e 
                ON u.user_id = e.user_id
                ORDER BY u.user_id DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    public function getById($id) {

                $sql = "SELECT 
                    u.*,
                    u.user_id AS id,
                    e.employee_id,
                    e.employee_code,
                    COALESCE(NULLIF(e.full_name, ''), NULLIF(u.full_name, ''), u.username) AS full_name,
                    COALESCE(NULLIF(e.email, ''), NULLIF(u.email, '')) AS email,
                    COALESCE(NULLIF(e.phone, ''), NULLIF(u.phone, '')) AS phone
                FROM users u
                LEFT JOIN employees e
                ON u.user_id = e.user_id
                WHERE u.user_id = ?";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {

        $sql = "SELECT *
                FROM users
                WHERE username = ?";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check username exists
     */
    public function checkUsernameExists($username, $excludeId = null) {

        $sql = "SELECT COUNT(*) as count 
                FROM users 
                WHERE username = ?";

        if ($excludeId) {

            $sql .= " AND user_id != ?";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$username, $excludeId]);

        } else {

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([$username]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Create user
     */
    public function create($data) {

        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_BCRYPT
        );

        $sql = "INSERT INTO users
                (
                    username,
                    password,
                    full_name,
                    email,
                    phone,
                    role,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([

            $data['username'],
            $hashedPassword,
            $data['full_name'] ?? $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['role'] ?? 'employee',
            strtolower($data['status'] ?? 'active')

        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update user
     */
    public function update($id, $data) {

        $sql = "UPDATE users SET

                    username = ?,
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    role = ?,
                    status = ?

                WHERE user_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([

            $data['username'],
            $data['full_name'] ?? '',
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['role'] ?? 'employee',
            strtolower($data['status'] ?? 'active'),
            $id

        ]);
    }

    /**
     * Link one employee to a user account.
     */
    public function linkEmployee($userId, $employeeId = null) {

        $stmt = $this->pdo->prepare(
            "UPDATE employees
             SET user_id = NULL
             WHERE user_id = ?"
        );
        $stmt->execute([$userId]);

        if (empty($employeeId)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE employees
             SET user_id = ?
             WHERE employee_id = ?"
        );

        return $stmt->execute([$userId, $employeeId]);
    }

    /**
     * Update password
     */
    public function updatePassword($id, $password) {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        $sql = "UPDATE users 
                SET password = ?
                WHERE user_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            $hashedPassword,
            $id
        ]);
    }

    /**
     * Delete user
     */
    public function delete($id) {

        $sql = "DELETE FROM users
                WHERE user_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([$id]);
    }

    /**
     * Login authentication
     */
    public function authenticate($username, $password) {

        $user = $this->getByUsername($username);

        if (!$user) {

            return [
                'success' => false,
                'message' => 'Username does not exist'
            ];
        }

        if (strtolower($user['status'] ?? '') !== 'active') {

            return [
                'success' => false,
                'message' => 'Account has been locked'
            ];
        }

        /**
         * Nếu DB đang dùng password thường
         */
        if ($password === $user['password']) {

            return [
                'success' => true,
                'user' => $user
            ];
        }

        /**
         * Nếu dùng password_hash
         */
        if (!password_verify($password, $user['password'])) {

            return [
                'success' => false,
                'message' => 'Incorrect password'
            ];
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }
}

?>
