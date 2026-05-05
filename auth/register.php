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

        <!-- Decorative background elements -->
        <div class="auth-bg-orb auth-bg-orb-1"></div>
        <div class="auth-bg-orb auth-bg-orb-2"></div>
        <div class="auth-bg-grid"></div>

        <!-- Floating tool cards -->
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
                <span>Performance and tracking</span>
            </div>
        </div>

        <!-- Centered register card -->
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
                        value="<?= htmlspecialchars($full_name ?? '') ?>"
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
                        <option value="junior" <?= (($role ?? '') === 'junior') ? 'selected' : '' ?>>Junior Technician</option>
                        <option value="senior" <?= (($role ?? '') === 'senior') ? 'selected' : '' ?>>Senior Technician</option>
                        <option value="admin" <?= (($role ?? '') === 'admin') ? 'selected' : '' ?>>Administrator</option>
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

            <a href="/" class="back-home">← Back to home</a>
        </section>

    </main>

</body>
</html>