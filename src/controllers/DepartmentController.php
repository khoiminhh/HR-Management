<?php

class DepartmentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureDepartmentPositionsTable();
    }

    public function getAllWithEmployeeCounts() {
        $hasDescription = $this->hasDescriptionColumn();
        $descriptionSelect = $hasDescription ? "d.description," : "'' AS description,";

        $sql = "SELECT d.department_id AS id, d.department_name, $descriptionSelect COUNT(e.employee_id) AS employee_count
                FROM departments d
                LEFT JOIN employees e ON e.department_id = d.department_id
                GROUP BY d.department_id, d.department_name" . ($hasDescription ? ", d.description" : "") . "
                ORDER BY d.department_name";

        $departments = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $positionsByDepartment = $this->getPositionRowsGrouped();

        foreach ($departments as &$department) {
            $department['positions'] = $positionsByDepartment[$department['id']] ?? [];
        }

        return $departments;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT *, department_id AS id FROM departments WHERE department_id = ?");
        $stmt->execute([$id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($department) {
            $positions = $this->getPositionsByDepartmentId($department['id']);
            $department['positions'] = $positions;
            $department['positions_text'] = implode(PHP_EOL, $positions);
        }

        return $department;
    }

    public function save($data = null) {
        $data = $data ?? $_POST;
        $name = trim($data['department_name'] ?? '');
        $description = trim($data['description'] ?? '');

        if ($name === '') {
            return false;
        }

        $hasDescription = $this->hasDescriptionColumn();

        try {
            $this->pdo->beginTransaction();

            if (!empty($data['id'])) {
                if ($hasDescription) {
                    $sql = "UPDATE departments SET department_name = :name, description = :desc WHERE department_id = :id";
                    $params = [':name' => $name, ':desc' => $description, ':id' => $data['id']];
                } else {
                    $sql = "UPDATE departments SET department_name = :name WHERE department_id = :id";
                    $params = [':name' => $name, ':id' => $data['id']];
                }
                $departmentId = (int)$data['id'];
            } else {
                if ($hasDescription) {
                    $sql = "INSERT INTO departments (department_name, description) VALUES (:name, :desc)";
                    $params = [':name' => $name, ':desc' => $description];
                } else {
                    $sql = "INSERT INTO departments (department_name) VALUES (:name)";
                    $params = [':name' => $name];
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }

            if (empty($departmentId)) {
                $departmentId = (int)$this->pdo->lastInsertId();
            }

            if (array_key_exists('positions', $data)) {
                $this->syncPositions($departmentId, $data['positions']);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function update($id, $name, $description = '') {
        return $this->save([
            'id' => $id,
            'department_name' => $name,
            'description' => $description,
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM departments WHERE department_id = ?");
        return $stmt->execute([$id]);
    }

    public function getPositionById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM department_positions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function savePosition($data) {
        $departmentId = (int)($data['department_id'] ?? 0);
        $positionName = trim($data['position_name'] ?? '');

        if ($departmentId <= 0 || $positionName === '') {
            return false;
        }

        try {
            $this->ensurePositionExists($positionName);

            if (!empty($data['position_id'])) {
                $stmt = $this->pdo->prepare("UPDATE department_positions SET position_name = ? WHERE id = ? AND department_id = ?");
                return $stmt->execute([$positionName, (int)$data['position_id'], $departmentId]);
            }

            $stmt = $this->pdo->prepare("INSERT IGNORE INTO department_positions (department_id, position_name) VALUES (?, ?)");
            return $stmt->execute([$departmentId, $positionName]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deletePosition($id) {
        $stmt = $this->pdo->prepare("DELETE FROM department_positions WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    private function hasDescriptionColumn() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM departments LIKE 'description'");
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function ensureDepartmentPositionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS department_positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    department_id INT NOT NULL,
                    position_name VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_department_position (department_id, position_name),
                    CONSTRAINT fk_department_positions_department
                        FOREIGN KEY (department_id) REFERENCES departments(department_id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->pdo->exec($sql);

        $seedSql = "INSERT IGNORE INTO department_positions (department_id, position_name)
                    SELECT d.department_id, p.position_name
                    FROM departments d
                    JOIN employees e ON e.department_id = d.department_id
                    JOIN positions p ON e.position_id = p.position_id
                    WHERE p.position_name IS NOT NULL AND p.position_name != ''";
        $this->pdo->exec($seedSql);
    }

    private function getPositionsByDepartmentId($departmentId) {
        try {
            $stmt = $this->pdo->prepare("SELECT position_name FROM department_positions WHERE department_id = ? ORDER BY position_name");
            $stmt->execute([$departmentId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'position_name');
        } catch (PDOException $e) {
            return [];
        }
    }

    private function getPositionRowsGrouped() {
        try {
            $stmt = $this->pdo->query("SELECT id, department_id, position_name FROM department_positions ORDER BY position_name");
        } catch (PDOException $e) {
            return [];
        }
        $positionsByDepartment = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $position) {
            $positionsByDepartment[$position['department_id']][] = $position;
        }

        return $positionsByDepartment;
    }

    private function syncPositions($departmentId, $positionsText) {
        $positions = $this->normalizePositions($positionsText);

        $delete = $this->pdo->prepare("DELETE FROM department_positions WHERE department_id = ?");
        $delete->execute([$departmentId]);

        if (empty($positions)) {
            return;
        }

        $insert = $this->pdo->prepare("INSERT IGNORE INTO department_positions (department_id, position_name) VALUES (?, ?)");
        foreach ($positions as $position) {
            $this->ensurePositionExists($position);
            $insert->execute([$departmentId, $position]);
        }
    }

    private function ensurePositionExists($positionName) {
        $positionName = trim((string)$positionName);

        if ($positionName === '') {
            return;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO positions (position_name) VALUES (?)");
            $stmt->execute([$positionName]);
        } catch (PDOException $e) {
            // Older installs may not have positions ready yet; department saving can still continue.
        }
    }

    private function normalizePositions($positionsText) {
        $rawPositions = preg_split('/[\r\n,;]+/', (string)$positionsText);
        $positions = [];

        foreach ($rawPositions as $position) {
            $position = trim($position);
            if ($position !== '' && !in_array($position, $positions, true)) {
                $positions[] = $position;
            }
        }

        return $positions;
    }
}

?>
