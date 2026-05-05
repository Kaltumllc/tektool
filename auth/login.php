<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

// auth_guard.php already handles session_start() and error_reporting,
// so we don't need to add them here.

if (is_logged_in()) {
    header('Location: /index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // REMOVED: session_start(); (It is already started in auth_guard.php)
            
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // Audit log
            $action = "Login: {$user['role']} #{$user['id']}";
            $stmt2  = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt2, 'is', $user['id'], $action);
            mysqli_stmt_execute($stmt2);

            // Role-based redirect
            switch ($user['role']) {
                case 'junior': header('Location: /junior/dashboard.php'); break;
                case 'senior': header('Location: /senior/dashboard.php'); break;
                case 'admin':  header('Location: /admin/dashboard.php');  break;
                default: header('Location: /index.php'); break;
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TekTool</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">⚙️ TekTool</div>
        <h2>Welcome Back</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@company.com"
                       value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>
        <p class="auth-footer">No account yet? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>