<?php
/**
 * ========================================
 * View: Danh sách Người dùng
 * ========================================
 */

require_once '../src/models/User.php';

Auth::requireRole(['Admin']);

$userModel = new User($pdo);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        
        // Không cho xóa tài khoản hiện tại
        if ($id == Auth::getCurrentUser()['id']) {
            $errors[] = 'Không thể xóa tài khoản hiện tại';
        } else {
            try {
                $userModel->delete($id);
                $message = 'Xóa tài khoản thành công!';
            } catch (Exception $e) {
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

$users = $userModel->getAll();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
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
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .nav {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav a:hover {
            background-color: #f0f0f0;
        }
        
        .nav a.active {
            background-color: #667eea;
            color: white;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .btn-add {
            background-color: #28a745;
            color: white;
        }
        
        .btn-add:hover {
            background-color: #218838;
        }
        
        .btn-edit {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .btn-edit:hover {
            background-color: #0056b3;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table thead {
            background-color: #667eea;
            color: white;
        }
        
        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status.active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-hr {
            background-color: #007bff;
            color: white;
        }
        
        .role-manager {
            background-color: #ffc107;
            color: #333;
        }
        
        .role-employee {
            background-color: #6c757d;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="ui-icon icon-users" aria-hidden="true"></i> Quản lý Người dùng</h1>
            <p>Quản lý tài khoản và phân quyền cho người dùng</p>
        </div>
        
        <!-- Navigation -->
        <div class="nav">
            <a href="?page=dashboard">Trang chủ</a>
            <a href="?page=employees">Nhân viên</a>
            <a href="?page=contracts">Hợp đồng</a>
            <a href="?page=salaries">Lương</a>
            <a href="?page=reports">Báo cáo</a>
            <a href="?page=users" class="active">Người dùng</a>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Top Bar -->
        <div class="top-bar">
            <h2>Danh sách tài khoản (<?= count($users) ?> tài khoản)</h2>
            <a href="?page=users&action=add" class="btn btn-add">+ Thêm tài khoản</a>
        </div>
        
        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                        <td>
                            <span class="role-badge role-<?= strtolower($user['role']) ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status <?= strtolower($user['status']) ?>">
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?page=users&action=edit&id=<?= $user['id'] ?>" class="btn btn-edit"><i class="ui-icon icon-edit" aria-hidden="true"></i></a>
                                <?php if ($user['id'] != Auth::getCurrentUser()['id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa tài khoản này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-delete" aria-label="Xóa"><i class="ui-icon icon-delete" aria-hidden="true"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

