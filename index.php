<?php
require_once 'config/db.php';
require_once 'includes/auth_guard.php';

// If already logged in redirect to their dashboard
if (is_logged_in()) {
    $role = $_SESSION['role'];
    header("Location: /$role/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TekTool — Field Tech Support Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="landing-page">

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="landing-nav-inner">
            <a href="/" class="landing-logo">
                <span class="brand-icon">⚙️</span>
                <span>TekTool</span>
            </a>

            <div class="nav-actions">
                <a href="/auth/login.php" class="nav-link">Sign In</a>
                <a href="/auth/register.php" class="nav-btn">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                ⚡ Built for C&amp;W Services Field Technicians
            </div>

            <h1 class="hero-title">
                Get Expert Help<br>
                On the Job Site — <span class="hero-accent">Instantly</span>
            </h1>

            <p class="hero-subtitle">
                TekTool connects field technicians with experienced lead techs in real time.
                Submit a request, get matched, resolve the issue, and build a smarter knowledge base for the whole team.
            </p>

            <div class="hero-actions">
                <a href="/auth/register.php" class="btn-primary">Start For Free</a>
                <a href="/auth/login.php" class="btn-secondary">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="features-inner">
            <div class="section-eyebrow">Platform Capabilities</div>
            <h2 class="section-title">Everything your field team needs</h2>
            <p class="section-subtitle">
                Built for fast troubleshooting, team visibility, secure access, and continuous knowledge sharing across field operations.
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Instant Matching</h3>
                    <p>Field techs get matched to the first available lead tech automatically — no waiting, no phone tag.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🧠</div>
                    <h3>AI-Powered Assistant</h3>
                    <p>Built-in AI helps resolve common issues first before escalating to a lead technician.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Knowledge Base</h3>
                    <p>Every resolved request becomes searchable knowledge for future troubleshooting and training.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Designed for the job site. Works smoothly on phones, tablets, and desktops with no app download required.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Admin Visibility</h3>
                    <p>Managers can monitor requests, resolution times, technician activity, and team performance in real time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Secure & Reliable</h3>
                    <p>Role-based access control, encrypted passwords, audit logging, and accountable issue tracking.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="how-it-works">
        <div class="features-inner">
            <div class="section-eyebrow">Workflow</div>
            <h2 class="section-title">How TekTool Works</h2>
            <p class="section-subtitle">
                A simple three-step process built for speed, accountability, and field reliability.
            </p>

            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Submit a Request</h3>
                    <p>Field tech describes the issue using title, category, location, asset, and details.</p>
                </div>

                <div class="step-arrow">→</div>

                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Get Matched</h3>
                    <p>TekTool assigns the request to the first available lead tech or support expert.</p>
                </div>

                <div class="step-arrow">→</div>

                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Resolve & Log</h3>
                    <p>The issue is resolved, documented, and saved into the knowledge base for future use.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <h2>Built for technicians who need answers fast.</h2>
        <p>
            Reduce downtime, improve support visibility, and turn every resolved issue into reusable field knowledge.
        </p>
        <a href="/auth/register.php" class="btn-primary">Start Using TekTool</a>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-content">
            <div class="legal-row">
                <span>&copy; 2026 <strong>Kaltum LLC</strong>. All Rights Reserved.</span>
                <span style="opacity: 0.35;">|</span>
                <span>Developed for the <strong>C&amp;W Services</strong> Field Tech Platform.</span>
            </div>

            <div class="footer-links">
                <a href="/privacy">Privacy</a>
                <a href="/terms">Terms</a>
                <a href="/security">Security</a>
                <a href="/support">Support</a>
            </div>
        </div>
    </footer>

</body>
</html>