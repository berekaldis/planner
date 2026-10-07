<?php
/**
 * CSRF Protection
 * Kaldis Coffee PLC
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

function get_planner_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function get_planner_csrf_field(): string {
    $token = get_planner_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return get_planner_csrf_token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return get_planner_csrf_field();
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool {
        $submittedToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? $_SESSION['_token'] ?? '';
        if (empty($submittedToken) || empty($sessionToken)) {
            return false;
        }
        return hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) {
                http_response_code(403);
                die('CSRF validation failed. Please refresh the page and try again.');
            }
        }
    }
}
