<?php
/**
 * ========================================
 * Model: Email
 * ========================================
 */

class Email {
    
    /**
     * Gửi email mẫu (Template)
     */
    public static function send($to, $subject, $body, $isHtml = true) {
        require_once '../config/email.php';
        
        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
        
        // Send
        $result = mail($to, $subject, $body, $headers);
        
        return [
            'success' => $result,
            'message' => $result ? 'Email sent successfully' : 'Failed to send email'
        ];
    }
    
    /**
     * Thông báo nhân viên mới (Welcome Email)
     */
    public static function sendWelcomeEmail($employee, $username, $password) {
        $to = $employee['email'];
        $subject = "Chào mừng bạn đến với Hệ thống Quản lý Nhân sự";
        
        $body = self::getTemplate('welcome', [
            'full_name' => $employee['full_name'],
            'username' => $username,
            'password' => $password,
            'position' => $employee['position'],
            'department' => $employee['department']
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo hợp đồng sắp hết hạn
     */
    public static function sendContractExpiringEmail($employee, $contract) {
        $to = $employee['email'];
        $subject = "⚠️ Thông báo: Hợp đồng của bạn sắp hết hạn";
        
        $daysLeft = ceil((strtotime($contract['end_date']) - time()) / 86400);
        
        $body = self::getTemplate('contract_expiring', [
            'full_name' => $employee['full_name'],
            'contract_number' => $contract['contract_number'],
            'end_date' => date('d/m/Y', strtotime($contract['end_date'])),
            'days_left' => $daysLeft
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo lương được thanh toán
     */
    public static function sendSalaryNotificationEmail($employee, $salary, $month, $year) {
        $to = $employee['email'];
        $subject = "💰 Thông báo lương tháng " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/" . $year;
        
        $body = self::getTemplate('salary_notification', [
            'full_name' => $employee['full_name'],
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year' => $year,
            'base_salary' => number_format($salary['base_salary'], 0, ',', '.'),
            'allowances' => number_format($salary['allowances'], 0, ',', '.'),
            'deductions' => number_format($salary['deductions'], 0, ',', '.'),
            'insurance' => number_format($salary['insurance'], 0, ',', '.'),
            'net_salary' => number_format($salary['net_salary'], 0, ',', '.'),
            'payment_date' => $salary['payment_date'] ? date('d/m/Y', strtotime($salary['payment_date'])) : 'Sắp diễn ra'
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo khen thưởng
     */
    public static function sendRewardNotificationEmail($employee, $reward) {
        $to = $employee['email'];
        $subject = "🎉 Chúc mừng! Bạn đã được khen thưởng";
        
        $body = self::getTemplate('reward_notification', [
            'full_name' => $employee['full_name'],
            'reward_title' => $reward['title'],
            'reward_date' => date('d/m/Y', strtotime($reward['date_issued'])),
            'reward_amount' => $reward['amount'] ? number_format($reward['amount'], 0, ',', '.') . ' đ' : 'Không',
            'reward_description' => $reward['description'] ?? 'Không có'
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo kỷ luật
     */
    public static function sendDisciplineNotificationEmail($employee, $discipline) {
        $to = $employee['email'];
        $subject = "⚠️ Thông báo kỷ luật";
        
        $body = self::getTemplate('discipline_notification', [
            'full_name' => $employee['full_name'],
            'discipline_title' => $discipline['title'],
            'discipline_date' => date('d/m/Y', strtotime($discipline['date_issued'])),
            'discipline_amount' => $discipline['amount'] ? number_format($discipline['amount'], 0, ',', '.') . ' đ' : 'Không',
            'discipline_description' => $discipline['description'] ?? 'Không có'
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo cập nhật tài khoản
     */
    public static function sendAccountUpdateEmail($user, $oldUsername) {
        $to = $user['email'];
        $subject = "ℹ️ Tài khoản của bạn đã được cập nhật";
        
        $body = self::getTemplate('account_update', [
            'full_name' => $user['full_name'],
            'old_username' => $oldUsername,
            'new_username' => $user['username'],
            'role' => self::getRoleLabel($user['role'])
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Thông báo quên mật khẩu
     */
    public static function sendForgotPasswordEmail($user, $resetToken) {
        $to = $user['email'];
        $subject = "🔐 Yêu cầu đặt lại mật khẩu";
        
        $resetLink = "http://localhost/hr-management/public/reset-password.php?token=" . $resetToken;
        
        $body = self::getTemplate('forgot_password', [
            'full_name' => $user['full_name'],
            'reset_link' => $resetLink,
            'expire_time' => '24 giờ'
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Lấy email template
     */
    private static function getTemplate($type, $data) {
        $templates = [
            'welcome' => self::welcomeTemplate($data),
            'contract_expiring' => self::contractExpiringTemplate($data),
            'salary_notification' => self::salaryNotificationTemplate($data),
            'reward_notification' => self::rewardNotificationTemplate($data),
            'discipline_notification' => self::disciplineNotificationTemplate($data),
            'account_update' => self::accountUpdateTemplate($data),
            'forgot_password' => self::forgotPasswordTemplate($data)
        ];
        
        return $templates[$type] ?? 'Email không tìm thấy';
    }
    
    /**
     * Welcome Email Template
     */
    private static function welcomeTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Chào mừng</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Chúc mừng bạn đã trở thành nhân viên của công ty chúng tôi!</p>
            
            <div class="info-box">
                <strong>📋 Thông tin tài khoản của bạn:</strong><br><br>
                <strong>Chức vụ:</strong> {$data['position']}<br>
                <strong>Phòng ban:</strong> {$data['department']}<br>
                <strong>Username:</strong> {$data['username']}<br>
                <strong>Mật khẩu tạm:</strong> {$data['password']}<br><br>
                <em style="color: red;">⚠️ Vui lòng đổi mật khẩu ngay sau lần đăng nhập đầu tiên</em>
            </div>
            
            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với phòng Nhân sự.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
        
        <div class="footer">
            <p>© 2026 Hệ thống Quản lý Nhân sự. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Contract Expiring Email Template
     */
    private static function contractExpiringTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .warning-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Thông báo quan trọng</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Hợp đồng lao động của bạn sắp hết hạn!</p>
            
            <div class="warning-box">
                <strong>📋 Thông tin hợp đồng:</strong><br><br>
                <strong>Số hợp đồng:</strong> {$data['contract_number']}<br>
                <strong>Ngày kết thúc:</strong> {$data['end_date']}<br>
                <strong>Thời gian còn lại:</strong> <span style="color: red;"><strong>{$data['days_left']} ngày</strong></span>
            </div>
            
            <p>Vui lòng liên hệ với phòng Nhân sự để thảo luận về việc gia hạn hợp đồng.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Salary Notification Email Template
     */
    private static function salaryNotificationTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: left; }
        .total-row { background-color: #e8f5e9; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Thông báo lương</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Bảng lương tháng <strong>{$data['month']}/{$data['year']}</strong> của bạn đã được xử lý.</p>
            
            <table>
                <tr>
                    <th>Chi tiết</th>
                    <th>Số tiền</th>
                </tr>
                <tr>
                    <td>Lương cơ bản</td>
                    <td>{$data['base_salary']} đ</td>
                </tr>
                <tr>
                    <td>Phụ cấp</td>
                    <td>{$data['allowances']} đ</td>
                </tr>
                <tr>
                    <td>Khấu trừ</td>
                    <td>-{$data['deductions']} đ</td>
                </tr>
                <tr>
                    <td>Bảo hiểm</td>
                    <td>-{$data['insurance']} đ</td>
                </tr>
                <tr class="total-row">
                    <td>LƯƠNG RÒNG</td>
                    <td style="color: #27ae60;">{$data['net_salary']} đ</td>
                </tr>
            </table>
            
            <p><strong>📅 Ngày thanh toán:</strong> {$data['payment_date']}</p>
            
            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với phòng Nhân sự.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Reward Notification Email Template
     */
    private static function rewardNotificationTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .reward-box { background-color: #e8f5e9; border-left: 4px solid #27ae60; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Chúc mừng!</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Chúng tôi rất vui mừng thông báo rằng bạn đã được khen thưởng!</p>
            
            <div class="reward-box">
                <strong>🏆 Chi tiết khen thưởng:</strong><br><br>
                <strong>Tên:</strong> {$data['reward_title']}<br>
                <strong>Ngày:</strong> {$data['reward_date']}<br>
                <strong>Số tiền:</strong> {$data['reward_amount']}<br>
                <strong>Mô tả:</strong> {$data['reward_description']}
            </div>
            
            <p>Đây là sự công nhận cho sự đóng góp xuất sắc của bạn. Chúng tôi hy vọng bạn sẽ tiếp tục phát huy!</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Discipline Notification Email Template
     */
    private static function disciplineNotificationTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .discipline-box { background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Thông báo kỷ luật</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Chúng tôi phải thông báo về một biện pháp kỷ luật áp dụng cho bạn.</p>
            
            <div class="discipline-box">
                <strong>📋 Chi tiết kỷ luật:</strong><br><br>
                <strong>Tên:</strong> {$data['discipline_title']}<br>
                <strong>Ngày:</strong> {$data['discipline_date']}<br>
                <strong>Hình phạt:</strong> {$data['discipline_amount']}<br>
                <strong>Mô tả:</strong> {$data['discipline_description']}
            </div>
            
            <p>Vui lòng xem xét lại các quy định công ty và cố gắng tuân thủ trong tương lai.</p>
            
            <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với phòng Nhân sự.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Account Update Email Template
     */
    private static function accountUpdateTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ℹ️ Cập nhật tài khoản</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Thông tin tài khoản của bạn đã được cập nhật.</p>
            
            <div class="info-box">
                <strong>📋 Thay đổi:</strong><br><br>
                <strong>Username cũ:</strong> {$data['old_username']}<br>
                <strong>Username mới:</strong> {$data['new_username']}<br>
                <strong>Role:</strong> {$data['role']}
            </div>
            
            <p>Nếu bạn không thực hiện các thay đổi này, vui lòng liên hệ ngay với phòng Nhân sự.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Forgot Password Email Template
     */
    private static function forgotPasswordTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .button { display: inline-block; background-color: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Đặt lại mật khẩu</h1>
        </div>
        
        <div class="content">
            <p>Xin chào <strong>{$data['full_name']}</strong>,</p>
            
            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
            
            <p>Vui lòng click vào nút bên dưới để đặt lại mật khẩu:</p>
            
            <center>
                <a href="{$data['reset_link']}" class="button">Đặt lại mật khẩu</a>
            </center>
            
            <p><strong>Lưu ý:</strong> Link này sẽ hết hạn sau {$data['expire_time']}.</p>
            
            <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
            
            <p>Trân trọng,<br>
            <strong>Phòng Nhân sự</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Helper: Get Role Label
     */
    private static function getRoleLabel($role) {
        $labels = [
            'Admin' => 'Quản trị viên',
            'HR' => 'Nhân sự',
            'Manager' => 'Quản lý',
            'Employee' => 'Nhân viên'
        ];
        return $labels[$role] ?? $role;
    }
}

?>