<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

if (is_logged_in()) {
    header('Location: /tektool/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? '';

    $allowed_roles = ['junior', 'senior'];

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $allowed_roles)) {
        $error = 'Invalid role selected.';
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt2  = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'ssss', $full_name, $email, $hashed, $role);

            if (mysqli_stmt_execute($stmt2)) {
                $user_id = mysqli_insert_id($conn);

                // If senior, insert availability row
                if ($role === 'senior') {
                    $stmt3 = mysqli_prepare($conn, "INSERT INTO availability (senior_id, is_available) VALUES (?, 1)");
                    mysqli_stmt_bind_param($stmt3, 'i', $user_id);
                    mysqli_stmt_execute($stmt3);
                }

                // Audit log
                $action = "New $role registered: $email";
                $stmt4  = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt4, 'is', $user_id, $action);
                mysqli_stmt_execute($stmt4);

                $success = 'Account created! <a href="login.php">Login here</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — TekTool</title>
    <link rel="stylesheet" href="/tektool/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">⚙️ TekTool</div>
        <h2>Create Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="John Smith"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@company.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min. 8 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
            </div>
            <div class="form-group">
    <label>My Role</label>
    <select name="role" required>
        <option value="">Select your role</option>
        <option value="junior" <?= (($_POST['role'] ?? '') === 'junior') ? 'selected' : '' ?>>Field Tech</option>
        <option value="senior" <?= (($_POST['role'] ?? '') === 'senior') ? 'selected' : '' ?>>Lead Tech</option>
    </select>
</div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>
        <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</body>
</html>