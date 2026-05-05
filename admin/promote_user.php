<?php
/**
 * TekTool — Admin: Promote / Change User Role
 * =============================================
 * Location: /admin/promote_user.php
 * Called via POST from manage_users.php
 * Protected: admin-only
 */

require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('admin');

$error   = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/manage_users.php');
    exit();
}

$target_id = (int)($_POST['user_id'] ?? 0);
$new_role  = trim($_POST['new_role'] ?? '');
$allowed   = ['junior', 'senior', 'admin'];

// Validation
if (!$target_id || !in_array($new_role, $allowed)) {
    header('Location: /admin/manage_users.php?error=invalid');
    exit();
}

// Prevent admin from demoting themselves
if ($target_id === (int)$_SESSION['user_id']) {
    header('Location: /admin/manage_users.php?error=self');
    exit();
}

// Get current user info
$stmt = mysqli_prepare($conn, "SELECT id, full_name, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $target_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$target = mysqli_fetch_assoc($result);

if (!$target) {
    header('Location: /admin/manage_users.php?error=notfound');
    exit();
}

$old_role = $target['role'];

// No-op if same role
if ($old_role === $new_role) {
    header('Location: /admin/manage_users.php?msg=nochange');
    exit();
}

// Update the role
$upd = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
mysqli_stmt_bind_param($upd, 'si', $new_role, $target_id);

if (!mysqli_stmt_execute($upd)) {
    header('Location: /admin/manage_users.php?error=dbfail');
    exit();
}

// If promoted to senior, ensure availability row exists
if ($new_role === 'senior') {
    $check = mysqli_prepare($conn, "SELECT senior_id FROM availability WHERE senior_id = ?");
    mysqli_stmt_bind_param($check, 'i', $target_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) === 0) {
        $ins = mysqli_prepare($conn, "INSERT INTO availability (senior_id, is_available) VALUES (?, 1)");
        mysqli_stmt_bind_param($ins, 'i', $target_id);
        mysqli_stmt_execute($ins);
    }
}

// If demoted away from senior, mark unavailable (clean up)
if ($old_role === 'senior' && $new_role !== 'senior') {
    $upd2 = mysqli_prepare($conn, "UPDATE availability SET is_available = 0 WHERE senior_id = ?");
    mysqli_stmt_bind_param($upd2, 'i', $target_id);
    mysqli_stmt_execute($upd2);
}

// Audit log
$admin_id = $_SESSION['user_id'];
$action   = "Role change: user #{$target_id} ({$target['full_name']}) changed from '$old_role' to '$new_role' by admin #{$admin_id}";
$log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
mysqli_stmt_bind_param($log, 'is', $admin_id, $action);
mysqli_stmt_execute($log);

header('Location: /admin/manage_users.php?msg=promoted&name=' . urlencode($target['full_name']) . '&role=' . urlencode($new_role));
exit();