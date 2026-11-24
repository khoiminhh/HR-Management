<?php
/**
 * ========================================
 * Email Configuration
 * ========================================
 */

// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'tranvankhoivtk@gmail.com');  // Thay email của bạn
define('MAIL_PASSWORD', 'Minhkhoi@123');     // Thay password app
define('MAIL_FROM_EMAIL', 'tranvankhoivtk@gmail.com');
define('MAIL_FROM_NAME', 'Hệ thống Quản lý Nhân sự');

// Hoặc dùng sendmail (nếu server hỗ trợ)
define('USE_SENDMAIL', false);

?>