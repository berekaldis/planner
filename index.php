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

// All authenticated users start from the OverView page
redirect('/overview.php');

