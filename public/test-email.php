<?php
/**
 * ========================================
 * Test Email
 * ========================================
 */

session_start();
require_once '../config/database.php';
require_once '../config/email.php';
require_once '../src/helpers/Auth.php';
require_once '../src/models/Email.php';

Auth::requireLogin();
Auth::requireRole(['Admin']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = $_POST['email'] ?? '';
    $testType = $_POST['test_type'] ?? '';
    
    if (empty($recipientEmail)) {
        $error = 'Email không được để trống';
    } else {
        // Gửi email test
        $result = Email::send(
            $recipientEmail,
            '📧 Test Email - Hệ thống Quản lý Nhân sự',
            <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Test Email Thành công!</h1>
        </div>
        <p>Đây là email test từ Hệ thống Quản lý Nhân sự.</p>
        <p>Nếu bạn nhận được email này, hệ thống gửi email đã hoạt động bình thường.</p>
        <p>Trân trọng,<br><strong>Hệ thống Quản lý Nhân sự</strong></p>
    </div>
</body>
</html>
HTML
        );
        
        if ($result['success']) {
            $message = '✅ Email test đã được gửi thành công!';
        } else {
            $error = '❌ Lỗi gửi email: ' . $result['message'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
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
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .btn:hover {
            background-color: #5568d3;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Test Email</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <div class="info-box">
                ℹ️ Gửi email test để kiểm tra cấu hình email có hoạt động không.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Email nhận test</label>
                    <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                </div>
                
                <button type="submit" class="btn">🚀 Gửi Email Test</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h3 style="margin-bottom: 15px;">⚙️ Cấu hình Email hiện tại:</h3>
            <pre style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; font-size: 12px;">
Host: <?= MAIL_HOST ?>
Port: <?= MAIL_PORT ?>
From: <?= MAIL_FROM_NAME ?> &lt;<?= MAIL_FROM_EMAIL ?>&gt;
            </pre>
        </div>
    </div>
</body>
</html>