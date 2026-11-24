<?php
/**
 * ========================================
 * Login Page
 * ========================================
 */

session_start();

// Nếu đã đăng nhập, redirect sang dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
require_once '../src/models/User.php';

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors[] = 'Username không được để trống';
    }
    
    if (empty($password)) {
        $errors[] = 'Mật khẩu không được để trống';
    }
    
    if (empty($errors)) {
        $userModel = new User($pdo);
        $result = $userModel->authenticate($username, $password);
        
        if ($result['success']) {
            // Lưu thông tin vào session
            $_SESSION['user'] = $result['user'];
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['role'] = $result['user']['role'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Nhân sự</title>
    <link rel="stylesheet" href="css/official-ui.css">
    <link rel="stylesheet" href="css/app-unified.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --teal: #0f766e;
            --amber: #d97706;
            --ink: #172033;
            --muted: #64748b;
            --line: #dbe4ef;
            --soft: #f4f7fb;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(15, 118, 110, .08)),
                #eef3f8;
            display: grid;
            place-items: center;
            padding: 28px;
        }

        .auth-shell {
            width: min(1120px, 100%);
            min-height: 660px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(430px, .72fr);
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(219, 228, 239, .95);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(23, 32, 51, .16);
        }

        .brand-panel {
            position: relative;
            background:
                linear-gradient(145deg, rgba(30,64,175,.94), rgba(15,118,110,.92)),
                url('https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1300&q=80');
            background-size: cover;
            background-position: center;
            color: #fff;
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            isolation: isolate;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(9, 20, 45, .58), rgba(9, 20, 45, .18));
            z-index: -1;
        }

        .brand-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-mark {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #fff;
            color: var(--primary-dark);
            display: grid;
            place-items: center;
            font-weight: 900;
            font-size: 18px;
            box-shadow: 0 10px 28px rgba(0,0,0,.18);
        }

        .brand-name {
            font-size: 17px;
            font-weight: 900;
            letter-spacing: .2px;
        }

        .brand-sub {
            font-size: 12px;
            color: rgba(255,255,255,.74);
            margin-top: 2px;
        }

        .brand-copy {
            max-width: 560px;
            padding: 48px 0;
        }

        .brand-copy h1 {
            font-size: 42px;
            line-height: 1.12;
            font-weight: 900;
            letter-spacing: 0;
            margin-bottom: 16px;
        }

        .brand-copy p {
            max-width: 520px;
            color: rgba(255,255,255,.82);
            font-size: 15px;
            line-height: 1.7;
        }

        .insight-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .insight {
            background: rgba(255,255,255,.13);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 10px;
            padding: 14px;
            backdrop-filter: blur(14px);
        }

        .insight strong {
            display: block;
            font-size: 22px;
            line-height: 1;
            margin-bottom: 7px;
        }

        .insight span {
            color: rgba(255,255,255,.76);
            font-size: 12px;
            font-weight: 700;
        }

        .login-panel {
            background: #fff;
            padding: 46px 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .auth-intro {
            margin-bottom: 28px !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            color: var(--ink) !important;
        }

        .auth-intro .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--teal);
            background: #ecfeff;
            border: 1px solid #a5f3fc;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .auth-intro h2 {
            color: var(--ink) !important;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 0;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .auth-intro p {
            color: var(--muted) !important;
            font-size: 14px;
            line-height: 1.55;
        }

        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            color: var(--ink);
            font-weight: 800;
            font-size: 13px;
        }

        .input-wrap {
            position: relative;
        }

        .auth-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
        }

        .auth-shell .form-group input[type="text"],
        .auth-shell .form-group input[type="password"] {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 46px !important;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fbfdff;
            color: var(--ink);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            transition: border-color .18s, box-shadow .18s, background .18s;
        }

        .auth-shell .form-group input::placeholder {
            color: #94a3b8;
            opacity: 1;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        .error-box {
            background: var(--danger-soft);
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 13px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 700;
        }

        .error-box ul { list-style: none; }
        .error-box li + li { margin-top: 6px; }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 2px 0 20px;
        }

        .remember-me {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .remember-me input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .support-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .support-link:hover { color: var(--primary-dark); }

        .btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--teal));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            transition: transform .18s, box-shadow .18s;
            box-shadow: 0 12px 24px rgba(37, 99, 235, .23);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(37, 99, 235, .28);
        }

        .btn-login:active { transform: translateY(0); }

        .demo-box {
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            margin-top: 20px;
            font-size: 12px;
            color: var(--muted);
        }

        .demo-title {
            display: block;
            color: var(--ink);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .demo-account {
            background: #fff;
            border: 1px solid #e8eef6;
            border-radius: 8px;
            padding: 10px;
        }

        .demo-account b {
            display: block;
            color: var(--ink);
            margin-bottom: 3px;
        }

        .login-foot {
            margin-top: 22px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            body { padding: 18px; }
            .auth-shell { grid-template-columns: 1fr; min-height: 0; }
            .brand-panel { min-height: 340px; padding: 30px; }
            .brand-copy { padding: 44px 0 28px; }
            .brand-copy h1 { font-size: 32px; }
            .login-panel { padding: 34px 28px; }
        }

        @media (max-width: 560px) {
            body { padding: 0; background: #fff; }
            .auth-shell { border: none; border-radius: 0; box-shadow: none; }
            .brand-panel { display: none; }
            .login-panel { min-height: 100vh; padding: 30px 20px; }
            .demo-grid { grid-template-columns: 1fr; }
            .form-row { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="brand-panel" aria-label="HR Management">
            <div class="brand-top">
                <div class="logo-mark" aria-hidden="true"></div>
                <div>
                    <div class="brand-name">HR Management</div>
                    <div class="brand-sub">Workforce Operations Portal</div>
                </div>
            </div>

            <div class="brand-copy">
                <h1>Quản trị nhân sự rõ ràng, nhanh và an toàn.</h1>
                <p>Theo dõi hồ sơ, hợp đồng, ca làm, chấm công và bảng lương trong một không gian làm việc thống nhất.</p>
            </div>

            <div class="insight-grid">
                <div class="insight"><strong>24/7</strong><span>Truy cập nội bộ</span></div>
                <div class="insight"><strong>RBAC</strong><span>Phân quyền vai trò</span></div>
                <div class="insight"><strong>HR</strong><span>Dữ liệu tập trung</span></div>
            </div>
        </section>

        <main class="login-panel">
            <div class="auth-intro">
                <div class="eyebrow"><i class="ui-icon icon-lock" aria-hidden="true"></i> Secure sign in</div>
                <h2>Đăng nhập hệ thống</h2>
                <p>Nhập tài khoản được cấp để tiếp tục vào dashboard quản lý nhân sự.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <div class="input-wrap">
                        <span class="auth-input-icon ui-icon icon-user" aria-hidden="true"></span>
                        <input type="text" id="username" name="username"
                               value="<?= htmlspecialchars($username) ?>"
                               placeholder="Nhập username" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="input-wrap">
                        <span class="auth-input-icon ui-icon icon-lock" aria-hidden="true"></span>
                        <input type="password" id="password" name="password"
                               placeholder="Nhập mật khẩu" required autocomplete="current-password">
                    </div>
                </div>

                <div class="form-row">
                    <label class="remember-me" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Ghi nhớ phiên đăng nhập</span>
                    </label>
                    <a class="support-link" href="reset-password.php">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login">Đăng nhập</button>
            </form>

            <div class="demo-box">
                <span class="demo-title">Tài khoản demo</span>
                <div class="demo-grid">
                    <div class="demo-account">
                        <b>Admin</b>
                        <span>admin / admin123</span>
                    </div>
                    <div class="demo-account">
                        <b>HR</b>
                        <span>hr / hr123</span>
                    </div>
                </div>
            </div>

            <div class="login-foot">© <?= date('Y') ?> HR Management System</div>
        </main>
    </div>
</body>
</html>
