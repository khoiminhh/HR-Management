<?php

class Auth
{
    /**
     * Start session
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check login
     */
    public static function check()
    {
        self::start();

        return isset($_SESSION['user']);
    }

    /**
     * Require login
     */
    public static function requireLogin()
    {
        self::start();

        if (!isset($_SESSION['user'])) {

            header("Location: login.php");

            exit;
        }
    }

    /**
     * Get current user
     */
    public static function user()
    {
        self::start();

        return $_SESSION['user'] ?? null;
    }

    public static function getCurrentUser()
    {
        $user = self::user();
        if ($user && isset($user['user_id']) && !isset($user['id'])) {
            $user['id'] = $user['user_id'];
        }

        return $user;
    }

    public static function getFullName()
    {
        $user = self::getCurrentUser();
        return $user['full_name'] ?? $user['username'] ?? '';
    }

    /**
     * Check role
     */
    public static function hasRole($roles = [])
    {
        self::start();

        if (!isset($_SESSION['user'])) {
            return false;
        }

        $userRole = strtolower($_SESSION['user']['role']);

        if (is_string($roles)) {
            $roles = [$roles];
        }

        $roles = array_map('strtolower', $roles);

        return in_array($userRole, $roles);
    }

    /**
     * Require role
     */
    public static function requireRole($roles = [])
    {
        if (!self::hasRole($roles)) {

            die("403 - Bạn không có quyền truy cập trang này");
        }
    }

    /**
     * Logout
     */
    public static function logout()
    {
        self::start();

        session_destroy();

        header("Location: login.php");

        exit;
    }
}

?>
