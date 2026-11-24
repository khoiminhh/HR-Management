<?php
/**
 * ========================================
 * View: Form Thêm/Sửa Người dùng
 * ========================================
 */

Auth::requireRole(['Admin']);

require_once '../src/models/User.php';
require_once '../src/models/Employee.php';

$userModel = new User($pdo);
$employeeModel = new Employee($pdo);

$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;
$user = null;
$employees = [];
$errors = [];
$message = '';
$formTitle = '';

// Lấy dữ liệu form
if ($action === 'edit' && $id) {
    $user = $userModel->getById($id);
    if (!$user) {
        $errors[] = 'Người dùng không tồn tại';
    }
    $formTitle = 'Sửa tài khoản';
} else {
    $formTitle = 'Thêm tài khoản mới';
}

$employees = $employeeModel->getAll();

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'employee_id' => $_POST['employee_id'] ?? '',
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role'] ?? 'Employee',
        'status' => $_POST['status'] ?? 'active'
    ];

    if (!empty($formData['employee_id'])) {
        $linkedEmployee = $employeeModel->getById($formData['employee_id']);
        if ($linkedEmployee) {
            $formData['full_name'] = $linkedEmployee['full_name'] ?? '';
            $formData['email'] = $linkedEmployee['email'] ?? null;
            $formData['phone'] = $linkedEmployee['phone'] ?? null;
        }
    }
    
    // Validation
    if (empty($formData['username'])) {
        $errors[] = 'Username không được để trống';
    } elseif ($userModel->checkUsernameExists($formData['username'], $action === 'edit' ? $id : null)) {
        $errors[] = 'Username này đã tồn tại';
    }
    
    if ($action === 'add') {
        if (empty($formData['password'])) {
            $errors[] = 'Mật khẩu không được để trống';
        } elseif (strlen($formData['password']) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
        
        if (empty($errors)) {
            try {
                $newUserId = $userModel->create($formData);
                $userModel->linkEmployee($newUserId, $formData['employee_id']);
                $message = 'Thêm tài khoản thành công!';
                header("Refresh: 2; url=?page=users");
            } catch (Exception $e) {
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
    } else {
        if (!empty($formData['password'])) {
            if (strlen($formData['password']) < 6) {
                $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
            }
        }
        
        if (empty($errors)) {
            try {
                $userModel->update($id, $formData);
                $userModel->linkEmployee($id, $formData['employee_id']);
                
                if (!empty($formData['password'])) {
                    $userModel->updatePassword($id, $formData['password']);
                }
                
                $message = 'Cập nhật tài khoản thành công!';
                header("Refresh: 2; url=?page=users");
            } catch (Exception $e) {
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

// Set giá trị mặc định
$defaultData = $user ?? [
    'employee_id' => '',
    'username' => '',
    'password' => '',
    'role' => 'Employee',
    'status' => 'active'
];

$defaultData['employee_id'] = $defaultData['employee_id'] ?? '';
$defaultData['status'] = ucfirst(strtolower($defaultData['status'] ?? 'active'));

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($formTitle) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .error-list li {
            margin: 5px 0;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            flex: 1;
            text-align: center;
        }
        
        .btn-submit {
            background-color: #667eea;
            color: white;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?= htmlspecialchars($formTitle) ?></h1>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label for="employee_id">Nhân viên</label>
                    <select id="employee_id" name="employee_id">
                        <option value="">-- Không liên kết --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" 
                                    <?= ($defaultData['employee_id'] == $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="username"><i class="ui-icon icon-user" aria-hidden="true"></i> Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($defaultData['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" 
                           <?= ($action === 'add') ? 'required' : '' ?>>
                    <?php if ($action === 'edit'): ?>
                        <small style="color: #999;">Để trống nếu không muốn đổi mật khẩu</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="Employee" <?= ($defaultData['role'] === 'Employee') ? 'selected' : '' ?>>Employee (Nhân viên)</option>
                        <option value="Manager" <?= ($defaultData['role'] === 'Manager') ? 'selected' : '' ?>>Manager (Quản lý)</option>
                        <option value="HR" <?= ($defaultData['role'] === 'HR') ? 'selected' : '' ?>>HR (Nhân sự)</option>
                        <option value="Admin" <?= ($defaultData['role'] === 'Admin') ? 'selected' : '' ?>>Admin (Quản trị)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="Active" <?= ($defaultData['status'] === 'Active') ? 'selected' : '' ?>>Hoạt động</option>
                        <option value="Inactive" <?= ($defaultData['status'] === 'Inactive') ? 'selected' : '' ?>>Khóa</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit"><i class="ui-icon icon-save" aria-hidden="true"></i> Lưu</button>
                    <a href="?page=users" class="btn btn-cancel"><i class="ui-icon icon-inactive" aria-hidden="true"></i> Hủy</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

