<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('junior');

$user_id = $_SESSION['user_id'];

// Filter
$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "AND hr.status = '$filter'" : '';

$result = mysqli_query($conn, "
    SELECT hr.id, hr.title, hr.description, hr.status,
           hr.created_at, hr.resolved_at,
           u.full_name as senior_name
    FROM help_requests hr
    LEFT JOIN users u ON hr.senior_id = u.id
    WHERE hr.junior_id = $user_id $where
    ORDER BY hr.created_at DESC
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Requests</h1>
    <a href="/tektool/junior/submit_request.php" class="btn btn-primary">+ New Request</a>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?status=all"         class="filter-tab <?= $filter==='all'         ? 'active':'' ?>">All</a>
    <a href="?status=open"        class="filter-tab <?= $filter==='open'        ? 'active':'' ?>">Open</a>
    <a href="?status=in_progress" class="filter-tab <?= $filter==='in_progress' ? 'active':'' ?>">In Progress</a>
    <a href="?status=resolved"    class="filter-tab <?= $filter==='resolved'    ? 'active':'' ?>">Resolved</a>
</div>

<div class="card">
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="empty-state">
            <p>No requests found. <a href="/tektool/junior/submit_request.php">Submit one now</a>.</p>
        </div>
    <?php else: ?>
        <div class="request-list">
            <?php while ($req = mysqli_fetch_assoc($result)): ?>
                <div class="request-item request-item-full">
                    <div class="request-info">
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            <span class="request-title"><?= htmlspecialchars($req['title']) ?></span>
                            <span class="status-badge status-<?= $req['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                            </span>
                        </div>
                        <span class="request-desc">
                            <?= htmlspecialchars(substr($req['description'], 0, 120)) ?>...
                        </span>
                        <span class="request-meta">
                            <?= $req['senior_name']
                                ? '👷 Assigned to ' . htmlspecialchars($req['senior_name'])
                                : '⏳ Awaiting assignment' ?>
                            &nbsp;·&nbsp;
                            Submitted <?= date('M j, Y g:i A', strtotime($req['created_at'])) ?>
                            <?php if ($req['resolved_at']): ?>
                                &nbsp;·&nbsp; Resolved <?= date('M j, Y', strtotime($req['resolved_at'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>