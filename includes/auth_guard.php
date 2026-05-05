<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Fix session persistence on production server
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit();
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: /auth/login.php');
        exit();
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}