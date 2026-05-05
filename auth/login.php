<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

// Initialize variables
$email = '';
$error = '';

if (is_logged_in()) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, password, role FROM users WHERE email = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user   = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Audit log
                $action = "Login: {$user['role']} #{$user['id']}";
                $stmt2  = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");

                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, 'is', $user['id'], $action);
                    mysqli_stmt_execute($stmt2);
                }

                // Role-based redirect
                switch ($user['role']) {
                    case 'junior':
                        header('Location: /junior/dashboard.php');
                        break;

                    case 'senior':
                        header('Location: /senior/dashboard.php');
                        break;

                    case 'admin':
                        header('Location: /admin/dashboard.php');
                        break;

                    default:
                        header('Location: /index.php');
                        break;
                }

                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Login system error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In — TekTool</title>

    <!-- Cache-busting version added so browser loads latest CSS -->
    <link rel="stylesheet" href="/assets/css/style.css?v=20260505">
</head>

<body class="auth-page">

    <main class="auth-shell">

        <!-- Left Brand Panel -->
        <section class="auth-left">
            <a href="/" class="auth-brand">
                <span class="brand-icon">⚙️</span>
                <span>TekTool</span>
            </a>

            <div class="auth-copy">
                <div class="hero-badge">
                    Field Tech Support Platform
                </div>

                <h1>
                    Expert help for field technicians — faster.
                </h1>

                <p>
                    Sign in to submit support requests, connect with available lead techs,
                    track resolutions, and build reusable knowledge from every field issue.
                </p>

                <div class="auth-highlights">
                    <div>
                        <strong>Real-time support</strong>
                        <span>Connect junior technicians with senior support quickly.</span>
                    </div>

                    <div>
                        <strong>Resolution tracking</strong>
                        <span>Log every fix, escalation, and technician action.</span>
                    </div>

                    <div>
                        <strong>Secure role access</strong>
                        <span>Separate dashboards for junior, senior, and admin users.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right Login Panel -->
        <section class="auth-right">
            <div class="auth-card">

                <div class="auth-card-header">
                    <div class="auth-logo-mobile">
                        ⚙️ TekTool
                    </div>

                    <h2>Welcome Back</h2>

                    <p>
                        Sign in to continue to your TekTool dashboard.
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="auth-alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">

                    <div class="form-group">
                        <label for="email">Email Address</label>

                        <input 
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@company.com"
                            value="<?= htmlspecialchars($email ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <input 
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="auth-submit">
                        Sign In
                    </button>

                </form>

                <p class="auth-switch">
                    No account yet?
                    <a href="/auth/register.php">Create an account</a>
                </p>

                <a href="/" class="back-home">
                    ← Back to home
                </a>

            </div>
        </section>

    </main>

</body>
</html>