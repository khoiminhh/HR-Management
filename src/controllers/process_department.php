<?php
/**
 * Tệp xử lý Thêm/Sửa Phòng ban
 */
// 1. Nhúng file cấu hình database (điều chỉnh đường dẫn cho đúng với dự án của bạn)
require_once __DIR__ . '/../../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Lấy dữ liệu từ Form
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['department_name'] ?? '');

    if (!empty($name)) {
        try {
            if ($id) {
                // Nếu có ID -> Thực hiện SỬA
                $sql = "UPDATE departments SET department_name = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $id]);
            } else {
                // Nếu không có ID -> Thực hiện THÊM MỚI
                $sql = "INSERT INTO departments (department_name) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name]);
            }
            
            // 3. Sau khi lưu xong, quay lại trang Thống kê phòng ban
            header("Location: ../../public/index.php?page=reports&type=department&msg=success");
            exit();

        } catch (PDOException $e) {
            die("Lỗi CSDL: " . $e->getMessage());
        }
    }
}