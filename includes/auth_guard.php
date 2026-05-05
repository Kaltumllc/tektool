<?php
// Suppress non-fatal notices to ensure redirects work
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

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