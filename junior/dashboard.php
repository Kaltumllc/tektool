<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('junior');

$user_id = $_SESSION['user_id'];

// Stats
$stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as count FROM help_requests WHERE junior_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = ['open' => 0, 'in_progress' => 0, 'resolved' => 0];
while ($row = mysqli_fetch_assoc($stats_result)) {
    $stats[$row['status']] = $row['count'];
}

// Recent requests
$stmt2 = mysqli_prepare($conn, "
    SELECT hr.id, hr.title, hr.status, hr.created_at, u.full_name as senior_name
    FROM help_requests hr
    LEFT JOIN users u ON hr.senior_id = u.id
    WHERE hr.junior_id = ?
    ORDER BY hr.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt2, 'i', $user_id);
mysqli_stmt_execute($stmt2);
$recent = mysqli_stmt_get_result($stmt2);

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h1>
    <a href="/tektool/junior/submit_request.php" class="btn btn-primary">+ New Request</a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number text-warning"><?= $stats['open'] ?></div>
        <div class="stat-label">Open</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-primary"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-success"><?= $stats['resolved'] ?></div>
        <div class="stat-label">Resolved</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= array_sum($stats) ?></div>
        <div class="stat-label">Total</div>
    </div>
</div>

<!-- Recent Requests -->
<div class="card">
    <div class="card-header">
        <h2>Recent Requests</h2>
        <a href="/tektool/junior/my_requests.php" class="btn-link">View all →</a>
    </div>
    <?php if (mysqli_num_rows($recent) === 0): ?>
        <div class="empty-state">
            <p>No requests yet. <a href="/tektool/junior/submit_request.php">Submit your first one</a>.</p>
        </div>
    <?php else: ?>
        <div class="request-list">
            <?php while ($req = mysqli_fetch_assoc($recent)): ?>
                <div class="request-item">
                    <div class="request-info">
                        <span class="request-title"><?= htmlspecialchars($req['title']) ?></span>
                        <span class="request-meta">
                            <?= $req['senior_name'] ? 'Assigned to ' . htmlspecialchars($req['senior_name']) : 'Awaiting assignment' ?>
                            · <?= date('M j, g:i A', strtotime($req['created_at'])) ?>
                        </span>
                    </div>
                    <span class="status-badge status-<?= $req['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                    </span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>