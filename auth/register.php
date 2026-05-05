<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

if (is_logged_in()) {
    header('Location: /index.php');
    exit();
}

$error = '';
$success = '';

$full_name = '';
$email = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? '';

    $allowed_roles = ['junior', 'senior'];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Invalid role selected.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = 'An account with that email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                $stmt2 = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
                );

                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, 'ssss', $full_name, $email, $hashed, $role);

                    if (mysqli_stmt_execute($stmt2)) {
                        $user_id = mysqli_insert_id($conn);

                        if ($role === 'senior') {
                            $stmt3 = mysqli_prepare(
                                $conn,
                                "INSERT INTO availability (senior_id, is_available) VALUES (?, 1)"
                            );

                            if ($stmt3) {
                                mysqli_stmt_bind_param($stmt3, 'i', $user_id);
                                mysqli_stmt_execute($stmt3);
                            }
                        }

                        $action = "New {$role} registered: {$email}";
                        $stmt4 = mysqli_prepare(
                            $conn,
                            "INSERT INTO audit_log (user_id, action) VALUES (?, ?)"
                        );

                        if ($stmt4) {
                            mysqli_stmt_bind_param($stmt4, 'is', $user_id, $action);
                            mysqli_stmt_execute($stmt4);
                        }

                        $success = 'Account created successfully.';
                        $full_name = '';
                        $email = '';
                        $role = '';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                } else {
                    $error = 'Registration system error. Please try again later.';
                }
            }
        } else {
            $error = 'Registration system error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account — TekTool</title>

    <link rel="stylesheet" href="/assets/css/style.css?v=20260505">
</head>

<body class="auth-page auth-page-centered">

    <main class="auth-center-shell">

        <div class="auth-bg-orb auth-bg-orb-1"></div>
        <div class="auth-bg-orb auth-bg-orb-2"></div>
        <div class="auth-bg-grid"></div>

        <div class="tool-float tool-float-1">
            <div class="tool-float-icon">🔧</div>
            <div class="tool-float-text">
                <strong>Work Orders</strong>
                <span>Track field issues fast</span>
            </div>
        </div>

        <div class="tool-float tool-float-2">
            <div class="tool-float-icon">⚙️</div>
            <div class="tool-float-text">
                <strong>Lead Tech Support</strong>
                <span>Get matched instantly</span>
            </div>
        </div>

        <div class="tool-float tool-float-3">
            <div class="tool-float-icon">📋</div>
            <div class="tool-float-text">
                <strong>Knowledge Base</strong>
                <span>Every fix is documented</span>
            </div>
        </div>

        <div class="tool-float tool-float-4">
            <div class="tool-float-icon">📈</div>
            <div class="tool-float-text">
                <strong>Admin Visibility</strong>
                <span>Performance tracking</span>
            </div>
        </div>

        <section class="auth-card register-card">

            <div class="auth-card-header">
                <a href="/" class="register-brand">
                    <span class="brand-icon">⚙️</span>
                    <span>TekTool</span>
                </a>

                <h2>Create Account</h2>

                <p>
                    Join TekTool to submit requests, collaborate with lead techs,
                    and build reusable field knowledge.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="auth-success">
                    <?= htmlspecialchars($success) ?>
                    <a href="/auth/login.php"> Sign in here.</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">

                <div class="form-group">
                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="John Smith"
                        value="<?= htmlspecialchars($full_name) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@company.com"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Min. 8 characters"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="role">My Role</label>

                    <select id="role" name="role" required>
                        <option value="">Select your role</option>

                        <option value="junior" <?= ($role === 'junior') ? 'selected' : '' ?>>
                            Field Tech
                        </option>

                        <option value="senior" <?= ($role === 'senior') ? 'selected' : '' ?>>
                            Lead Tech
                        </option>
                    </select>
                </div>

                <button type="submit" class="auth-submit">
                    Create Account
                </button>

            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="/auth/login.php">Sign in</a>
            </p>

            <a href="/" class="back-home">
                ← Back to home
            </a>

        </section>

    </main>

</body>
</html>