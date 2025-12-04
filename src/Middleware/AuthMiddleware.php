<?php

namespace App\Middleware;

class AuthMiddleware
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        // Check for session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            header('Location: login.php?timeout=1');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    public static function checkAdmin()
    {
        self::check();
        if ($_SESSION['role'] !== 'admin') {
            // Redirect non-admins to dashboard or show error
            header('Location: index.php');
            exit;
        }
    }
}
