<?php
/**
 * Authentication Module
 * Kaldis Coffee PLC
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        if (!empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role_name, r.display_name as role_display_name,
                       d.department_name, d.department_code
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.id = ? AND u.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user'] = $user;
                return $user;
            } else {
                self::logout();
            }
        } catch (PDOException $e) {
            error_log("Auth::user error: " . $e->getMessage());
        }

        return null;
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function attempt(string $username, string $password): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role_name, r.display_name as role_display_name,
                       d.department_name, d.department_code
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE (u.username = :uname OR u.email = :uemail)
                  AND u.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([':uname' => $username, ':uemail' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['user'] = $user;

                // Log audit
                require_once __DIR__ . '/audit.php';
                audit_log($user['id'], 'LOGIN', 'auth', (string)$user['id'], null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                return true;
            }
        } catch (PDOException $e) {
            error_log("Auth::attempt error: " . $e->getMessage());
        }

        return false;
    }

    public static function logout(): void {
        if (self::check()) {
            $userId = self::id();
            require_once __DIR__ . '/audit.php';
            audit_log($userId, 'LOGOUT', 'auth', (string)$userId);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
            redirect('/login.php');
        }
    }
}
