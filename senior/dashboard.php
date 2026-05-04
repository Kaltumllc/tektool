<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('senior');

$user_id = $_SESSION['user_id'];

// Get availability
$stmt = mysqli_prepare($conn, "SELECT is_available FROM availability WHERE senior_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$avail_result = mysqli_stmt_get_result($stmt);
$avail = mysqli_fetch_assoc($avail_result);
$is_available = $avail['is_available'] ?? 0;

// Stats
$stmt2 = mysqli_prepare($conn, "SELECT status, COUNT(*) as count FROM help_requests WHERE senior_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt2, 'i', $user_id);
mysqli_stmt_execute($stmt2);
$stats_result = mysqli_stmt_get_result($stmt2);
$stats = ['open' => 0, 'in_progress' => 0, 'resolved' => 0];
while ($row = mysqli_fetch_assoc($stats_result)) {
    $stats[$row['status']] = $row['count'];
}

// Open requests (unassigned)
$open = mysqli_query($conn, "
    SELECT hr.id, hr.title, hr.description, hr.created_at, u.full_name as junior_name
    FROM help_requests hr
    JOIN users u ON hr.junior_id = u.id
    WHERE hr.status = 'open'
    ORDER BY hr.created_at ASC
    LIMIT 10
");

// My active requests
$stmt3 = mysqli_prepare($conn, "
    SELECT hr.id, hr.title, hr.status, hr.created_at, u.full_name as junior_name
    FROM help_requests hr
    JOIN users u ON hr.junior_id = u.id
    WHERE hr.senior_id = ? AND hr.status = 'in_progress'
    ORDER BY hr.created_at DESC
");
mysqli_stmt_bind_param($stmt3, 'i', $user_id);
mysqli_stmt_execute($stmt3);
$active = mysqli_stmt_get_result($stmt3);

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Lead Tech Dashboard</h1>
    <div class="availability-toggle">
        <span>Status:</span>
        <label class="toggle-switch">
            <input type="checkbox" id="availToggle" <?= $is_available ? 'checked' : '' ?>
                   onchange="updateAvailability(this.checked)">
            <span class="toggle-slider"></span>
        </label>
        <span id="availLabel" class="<?= $is_available ? 'text-success' : 'text-danger' ?>">
            <?= $is_available ? 'Available' : 'Unavailable' ?>
        </span>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number text-primary"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-success"><?= $stats['resolved'] ?></div>
        <div class="stat-label">Resolved</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= array_sum($stats) ?></div>
        <div class="stat-label">Total Handled</div>
    </div>
</div>

<!-- Open Requests -->
<div class="card">
    <div class="card-header">
        <h2>Open Requests <span class="count-badge"><?= mysqli_num_rows($open) ?></span></h2>
    </div>
    <?php if (mysqli_num_rows($open) === 0): ?>
        <div class="empty-state"><p>No open requests right now. ✅</p></div>
    <?php else: ?>
        <div class="request-list">
            <?php while ($req = mysqli_fetch_assoc($open)): ?>
                <div class="request-item">
                    <div class="request-info">
                        <span class="request-title"><?= htmlspecialchars($req['title']) ?></span>
                        <span class="request-meta">
                            From <?= htmlspecialchars($req['junior_name']) ?>
                            · <?= date('M j, g:i A', strtotime($req['created_at'])) ?>
                        </span>
                        <span class="request-desc"><?= htmlspecialchars(substr($req['description'], 0, 100)) ?>...</span>
                    </div>
                    <form method="POST" action="/tektool/senior/active_request.php">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- My Active Requests -->
<?php if (mysqli_num_rows($active) > 0): ?>
<div class="card">
    <div class="card-header"><h2>My Active Requests</h2></div>
    <div class="request-list">
        <?php while ($req = mysqli_fetch_assoc($active)): ?>
            <div class="request-item">
                <div class="request-info">
                    <span class="request-title"><?= htmlspecialchars($req['title']) ?></span>
                    <span class="request-meta">From <?= htmlspecialchars($req['junior_name']) ?></span>
                </div>
                <a href="/tektool/senior/active_request.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline">Continue</a>
            </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<script>
function updateAvailability(isAvailable) {
    fetch('/tektool/api/set_availability.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({available: isAvailable ? 1 : 0})
    })
    .then(r => r.json())
    .then(data => {
        const label = document.getElementById('availLabel');
        label.textContent = isAvailable ? 'Available' : 'Unavailable';
        label.className = isAvailable ? 'text-success' : 'text-danger';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>