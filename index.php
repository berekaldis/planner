<?php
/**
 * Application Entry & Router
 * Kaldis Coffee PLC
 */

if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$role = Permissions::getRole();

switch ($role) {
    case Permissions::ROLE_SUPER_ADMIN:
    case Permissions::ROLE_IT_ADMIN:
        redirect('/admin/dashboard.php');
        break;

    case Permissions::ROLE_GM:
        redirect('/management/dashboard.php');
        break;

    case Permissions::ROLE_DEPT_HEAD:
        redirect('/department/dashboard.php');
        break;

    case Permissions::ROLE_HR:
        redirect('/performance/weekly.php');
        break;

    default:
        redirect('/department/dashboard.php');
        break;
}
