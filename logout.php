<?php
/**
 * Logout Controller
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/includes/auth.php';

Auth::logout();
redirect('/login.php');
