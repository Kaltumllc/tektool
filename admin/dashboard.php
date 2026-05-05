<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('admin');

// 1. Platform Stats - Synchronized with your ENUM states
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'] ?? 0;
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM help_requests"))['c'] ?? 0;

// Update: Counting both 'pending' and 'open' as "Open" for the UI
$open_requests  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM help_requests WHERE status IN ('pending', 'open')"))['c'] ?? 0;

$resolved       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM help_requests WHERE status='resolved'"))['c'] ?? 0;
$seniors_avail  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM availability WHERE is_available=1"))['c'] ?? 0;

// 2. Recent activity - Audit Log
$activity = mysqli_query($conn, "
    SELECT al.action, al.logged_at, u.full_name
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.logged_at DESC
    LIMIT 8
");

// 3. Recent requests - Detailed View
$requests = mysqli_query($conn, "
    SELECT hr.id, hr.title, hr.status, hr.created_at,
           j.full_name as junior_name, s.full_name as senior_name
    FROM help_requests hr
    JOIN users j ON hr.junior_id = j.id
    LEFT JOIN users s ON hr.senior_id = s.id
    ORDER BY hr.created_at DESC
    LIMIT 8
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <span class="text-muted"><?= date('l, F j, Y') ?></span>
</div>

<div class="stats-grid stats-grid-5">
    <div class="stat-card">
        <div class="stat-number"><?= $total_users ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-success"><?= $seniors_avail ?></div>
        <div class="stat-label">Seniors Available</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $total_requests ?></div>
        <div class="stat-label">Total Requests</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-warning"><?= $open_requests ?></div>
        <div class="stat-label">Open / Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-success"><?= $resolved ?></div>
        <div class="stat-label">Resolved</div>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <div class="card-header">
            <h2>Recent Requests</h2>
        </div>
        <div class="request-list">
            <?php if (!$requests || mysqli_num_rows($requests) === 0): ?>
                <div class="empty-state"><p>No requests yet.</p></div>
            <?php else: ?>
                <?php while ($req = mysqli_fetch_assoc($requests)): ?>
                    <div class="request-item">
                        <div class="request-info">
                            <span class="request-title"><?= htmlspecialchars($req['title']) ?></span>
                            <span class="request-meta">
                                <?= htmlspecialchars($req['junior_name']) ?>
                                <?= $req['senior_name'] ? '→ ' . htmlspecialchars($req['senior_name']) : '' ?>
                            </span>
                        </div>
                        <span class="status-badge status-<?= $req['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Activity Log</h2></div>
        <div class="activity-list">
            <?php if (!$activity || mysqli_num_rows($activity) === 0): ?>
                 <div class="empty-state"><p>No activity recorded.</p></div>
            <?php else: ?>
                <?php while ($log = mysqli_fetch_assoc($activity)): ?>
                    <div class="activity-item">
                        <div class="activity-detail">
                            <span class="activity-user"><strong><?= htmlspecialchars($log['full_name'] ?? 'System') ?></strong></span>
                            <span class="activity-action"><?= htmlspecialchars($log['action']) ?></span>
                        </div>
                        <span class="activity-time"><?= date('M j, g:i A', strtotime($log['logged_at'])) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>