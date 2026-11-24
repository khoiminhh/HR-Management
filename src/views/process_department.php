<?php
// File: public/process_department.php
require_once '../src/controllers/DepartmentController.php';

// Khởi tạo kết nối $pdo và controller (giả sử $pdo đã có sẵn)
$controller = new DepartmentController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thu thập dữ liệu từ form
    $data = [
        'id' => $_POST['id'] ?? null,
        'department_name' => $_POST['department_name'],
        'description' => $_POST['description']
    ];

    // Gọi hàm save trong controller
    $result = $controller->save($data);

    if ($result) {
        // Thành công: Chuyển hướng về trang danh sách
        header("Location: index.php?page=departments&status=success");
    } else {
        // Thất bại: Thông báo lỗi
        echo "Có lỗi xảy ra trong quá trình lưu dữ liệu.";
    }
    exit();
}