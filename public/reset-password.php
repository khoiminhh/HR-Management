<?php
/**
 * Reset Password Script
 */

require_once '../config/database.php';

$password = 'admin123';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    // Xóa user cũ
    $pdo->query("DELETE FROM users");
    
    // Thêm user mới
    $sql = "INSERT INTO users (username, password, role, status) VALUES
            ('admin', ?, 'Admin', 'Active'),
            ('hr', ?, 'HR', 'Active'),
            ('manager', ?, 'Manager', 'Active')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashedPassword, $hashedPassword, $hashedPassword]);
    
    echo "<h2>✅ Reset mống, chờ nhập)
- ☐ **Nhớ tôi**: (checkbox không checked)
- 🔓 **Đăng nhập** (nút màu xanh tím)

**Thông tin Demo:**
- Username: `admin`
- Password: `admin123`
- Username: `hr`

---

## ✅ **GIẢI PHÁP:**

Vì mật khẩu bị lỗi, hãy làm theo **2 cách** tôi vừa hướng dẫn ở trên:

### **Cách 1 (Nhanh nhất):** Reset password qua phpMyAdmin
```sql
DELETE FROM users;
INSERT INTO users (username, password, role, status) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7bật kh���u thành công!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<hr>";
    echo "<p><strong>Username:</strong> hr</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<hr>";
    echo "<p><strong>Username:</strong> manager</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<br><a href='login.php'><button>← Quay lại trang đăng nhập</button></a>";
    
} catch (Exception $e) {
    echo "<h2>❌ Lỗi: " . $e->getMessage() . "</h2>";
}
?>