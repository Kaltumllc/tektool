<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Guests can register normally.
| Admins can also use this page to create Field Tech / Lead Tech users.
| Other logged-in users should be redirected away.
*/
if (is_logged_in() && ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /index.php');
    exit();
}

$error = '';
$success = '';

$full_name = '';
$email = '';
$role = '';

$is_admin_creating_user = is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? '';

    // Public/admin registration should only create Field Tech or Lead Tech users
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
        // Check if email already exists
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

                        // If Lead Tech, create availability row
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

                        // Audit log
                        if ($is_admin_creating_user) {
                            $admin_id = (int)($_SESSION['user_id'] ?? 0);
                            $action = "Admin #{$admin_id} created new {$role} user: {$email}";
                            $log_user_id = $admin_id;
                        } else {
                            $action = "New {$role} registered: {$email}";
                            $log_user_id = $user_id;
                        }

                        $stmt4 = mysqli_prepare(
                            $conn,
                            "INSERT INTO audit_log (user_id, action) VALUES (?, ?)"
                        );

                        if ($stmt4) {
                            mysqli_stmt_bind_param($stmt4, 'is', $log_user_id, $action);
                            mysqli_stmt_execute($stmt4);
                        }

                        $success = $is_admin_creating_user
                            ? 'User account created successfully.'
                            : 'Account created successfully.';

                        // Clear form after successful registration
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

            mysqli_stmt_close($stmt);
        } else {
            $error = 'Registration system error. Please try again later.';
        }
    }
}

$page_title = $is_admin_creating_user ? 'Add User — TekTool' : 'Create Account — TekTool';
$heading    = $is_admin_creating_user ? 'Add New User' : 'Create Account';
$subtext    = $is_admin_creating_user
    ? 'Create a Field Tech or Lead Tech account for your TekTool team.'
    : 'Join TekTool to submit requests, collaborate with lead techs, and build reusable field knowledge.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title) ?></title>

    <link rel="stylesheet" href="/assets/css/style.css?v=20260505-4">
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
                <a href="<?= $is_admin_creating_user ? '/admin/manage_users.php' : '/' ?>" class="register-brand">
                    <span class="brand-icon">⚙️</span>
                    <span>TekTool</span>
                </a>

                <h2><?= htmlspecialchars($heading) ?></h2>

                <p>
                    <?= htmlspecialchars($subtext) ?>
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

                    <?php if ($is_admin_creating_user): ?>
                        <a href="/admin/manage_users.php"> Back to Manage Users.</a>
                    <?php else: ?>
                        <a href="/auth/login.php"> Sign in here.</a>
                    <?php endif; ?>
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
                        <option value="">Select role</option>

                        <option value="junior" <?= ($role === 'junior') ? 'selected' : '' ?>>
                            Field Tech
                        </option>

                        <option value="senior" <?= ($role === 'senior') ? 'selected' : '' ?>>
                            Lead Tech
                        </option>
                    </select>
                </div>

                <button type="submit" class="auth-submit">
                    <?= $is_admin_creating_user ? 'Create User' : 'Create Account' ?>
                </button>

            </form>

            <?php if ($is_admin_creating_user): ?>
                <p class="auth-switch">
                    Finished?
                    <a href="/admin/manage_users.php">Return to Manage Users</a>
                </p>

                <a href="/admin/dashboard.php" class="back-home">
                    ← Back to Admin Dashboard
                </a>
            <?php else: ?>
                <p class="auth-switch">
                    Already have an account?
                    <a href="/auth/login.php">Sign in</a>
                </p>

                <a href="/" class="back-home">
                    ← Back to home
                </a>
            <?php endif; ?>

        </section>

    </main>

</body>
</html>