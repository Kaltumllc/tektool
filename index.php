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

    <!-- Nav -->
    <nav class="landing-nav">
        <div class="landing-nav-inner">
            <span class="nav-brand">⚙️ TekTool</span>
            <div style="display:flex; gap:1rem;">
                <a href="/auth/login.php" class="btn btn-outline btn-sm">Sign In</a>
                <a href="/auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">Built for C&W Services Field Technicians</div>
            <h1 class="hero-title">Get Expert Help<br>On the Job Site — <span class="hero-accent">Instantly</span></h1>
            <p class="hero-sub">TekTool connects field technicians with experienced lead techs in real time. Submit a request, get matched, get back to work.</p>
            <div class="hero-cta">
                <a href="/auth/register.php" class="btn btn-primary btn-lg">Start For Free</a>
                <a href="/auth/login.php" class="btn btn-outline btn-lg">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="features-inner">
            <h2 class="section-title">Everything your team needs</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Instant Matching</h3>
                    <p>Field techs get matched to the first available lead tech automatically — no waiting, no phone tag.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🧠</div>
                    <h3>AI-Powered Assistant</h3>
                    <p>Built-in AI tries to resolve common issues instantly before escalating to a lead tech.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Knowledge Base</h3>
                    <p>Every resolved request is logged automatically, building a searchable library your whole team can use.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Designed for the job site. Works perfectly on any phone, tablet, or desktop — no app download needed.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Admin Visibility</h3>
                    <p>Managers get a real-time overview of all requests, resolution times, and team performance.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Reliable</h3>
                    <p>Role-based access control, encrypted passwords, and full audit logging on every action.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="how-it-works">
        <div class="features-inner">
            <h2 class="section-title">How TekTool Works</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Submit a Request</h3>
                    <p>Field tech describes the issue — title, category, location, and details.</p>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Get Matched</h3>
                    <p>TekTool instantly assigns the first available lead tech.</p>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Resolve & Log</h3>
                    <p>Lead tech resolves the issue and writes a note — saved forever to the knowledge base.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
    <div class="footer-content">
        <div class="footer-legal">
            <div class="legal-row">
                <span>&copy; 2026 <strong>Kaltum LLC</strong>. All Rights Reserved.</span>
                <span style="margin: 0 10px; opacity: 0.3;">|</span>
                <span>Developed for the <strong>C&W Services</strong> Field Tech Platform.</span>
            </div>
            
            <div class="footer-links">
                <a href="/privacy">Privacy</a>
                <a href="/terms">Terms</a>
                <a href="/security">Security</a>
                <a href="/support">Support</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>