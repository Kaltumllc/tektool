<?php
/**
 * TekTool — One-Time Admin Seed Script
 * ======================================
 * PURPOSE : Promote an existing user to 'admin' role, OR create a brand-new admin account.
 * SECURITY: DELETE THIS FILE from your server immediately after running it.
 *
 * Usage:
 *   https://tektool.onrender.com/setup/create_admin.php?secret=CHANGE_THIS_SECRET
 */

require_once '../config/db.php';

// ── CONFIGURE THESE THREE VALUES BEFORE UPLOADING ──────────────────────────
$SECRET       = 'Mustapha2026Tek!';   // Change to something only you know
$TARGET_EMAIL = 'your actual TekTool login email here'; // The account you want to promote to admin
$CREATE_IF_MISSING = false;              // Set true to create the account if it doesn't exist
$NEW_PASSWORD  = 'ChangeMe123!';        // Only used if CREATE_IF_MISSING = true
$NEW_FULL_NAME = 'Mustapha Ibrahim';    // Only used if CREATE_IF_MISSING = true
// ───────────────────────────────────────────────────────────────────────────

// Gate: secret key check
if (($_GET['secret'] ?? '') !== $SECRET) {
    http_response_code(403);
    die('<h2 style="color:red;">403 Forbidden — wrong or missing secret key.</h2>');
}

// Look up user
$stmt = mysqli_prepare($conn, "SELECT id, full_name, role FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $TARGET_EMAIL);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if ($user) {
    // User exists — promote to admin
    if ($user['role'] === 'admin') {
        echo '<p style="color:green;">✅ <strong>' . htmlspecialchars($user['full_name']) . '</strong> is already an admin. Nothing to do.</p>';
    } else {
        $old_role = $user['role'];
        $upd = mysqli_prepare($conn, "UPDATE users SET role = 'admin' WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'i', $user['id']);
        if (mysqli_stmt_execute($upd)) {
            // Log it
            $action = "Admin seed: promoted user #{$user['id']} ($TARGET_EMAIL) from $old_role to admin";
            $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
            mysqli_stmt_bind_param($log, 'is', $user['id'], $action);
            mysqli_stmt_execute($log);
            echo '<p style="color:green;">✅ <strong>' . htmlspecialchars($user['full_name']) . '</strong> has been promoted to <strong>admin</strong>.</p>';
            echo '<p>Previous role: <code>' . $old_role . '</code> → New role: <code>admin</code></p>';
        } else {
            echo '<p style="color:red;">❌ Update failed: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
        }
    }
} elseif ($CREATE_IF_MISSING) {
    // User doesn't exist — create them as admin
    $hashed = password_hash($NEW_PASSWORD, PASSWORD_BCRYPT);
    $ins = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')");
    mysqli_stmt_bind_param($ins, 'sss', $NEW_FULL_NAME, $TARGET_EMAIL, $hashed);
    if (mysqli_stmt_execute($ins)) {
        $new_id = mysqli_insert_id($conn);
        $action = "Admin seed: created new admin account #$new_id ($TARGET_EMAIL)";
        $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
        mysqli_stmt_bind_param($log, 'is', $new_id, $action);
        mysqli_stmt_execute($log);
        echo '<p style="color:green;">✅ Admin account created for <strong>' . htmlspecialchars($NEW_FULL_NAME) . '</strong> (' . htmlspecialchars($TARGET_EMAIL) . ')</p>';
        echo '<p>You can now log in with that email and password: <code>' . htmlspecialchars($NEW_PASSWORD) . '</code></p>';
    } else {
        echo '<p style="color:red;">❌ Insert failed: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
    }
} else {
    echo '<p style="color:orange;">⚠️ No account found for <strong>' . htmlspecialchars($TARGET_EMAIL) . '</strong>.</p>';
    echo '<p>Either fix the email address above, or set <code>$CREATE_IF_MISSING = true</code> to create it.</p>';
}
?>
<hr>
<p style="color:red; font-weight:bold;">⚠️ SECURITY REMINDER: Delete this file from your server right now.</p>
<p>SSH or Render Shell → <code>rm /path/to/setup/create_admin.php</code></p>