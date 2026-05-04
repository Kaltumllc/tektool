<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_login();

$search = trim($_GET['q'] ?? '');
$tag    = trim($_GET['tag'] ?? '');

$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($search) {
    $where   .= " AND (r.resolution_note LIKE ? OR hr.title LIKE ? OR hr.description LIKE ?)";
    $term     = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types   .= 'sss';
}

if ($tag) {
    $where   .= " AND r.tags LIKE ?";
    $params[] = "%$tag%";
    $types   .= 's';
}

$sql = "
    SELECT r.id, r.resolution_note, r.tags, r.created_at,
           hr.title, hr.description,
           j.full_name as junior_name,
           s.full_name as senior_name
    FROM resolutions r
    JOIN help_requests hr ON r.request_id = hr.id
    JOIN users j ON hr.junior_id = j.id
    JOIN users s ON hr.senior_id = s.id
    $where
    ORDER BY r.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$results = mysqli_stmt_get_result($stmt);

// Get all unique tags for tag cloud
$all_tags_result = mysqli_query($conn, "SELECT tags FROM resolutions WHERE tags IS NOT NULL AND tags != ''");
$tag_counts = [];
while ($row = mysqli_fetch_assoc($all_tags_result)) {
    foreach (explode(',', $row['tags']) as $t) {
        $t = trim($t);
        if ($t) $tag_counts[$t] = ($tag_counts[$t] ?? 0) + 1;
    }
}
arsort($tag_counts);

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>📚 Knowledge Base</h1>
    <span class="text-muted"><?= mysqli_num_rows($results) ?> resolution<?= mysqli_num_rows($results) !== 1 ? 's' : '' ?> found</span>
</div>

<!-- Search -->
<form method="GET" action="" style="margin-bottom:1.5rem;">
    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by keyword, issue, or solution..."
               style="flex:1; min-width:200px; padding:0.65rem 0.85rem; border:1px solid var(--border); border-radius:var(--radius); font-size:1rem;">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search || $tag): ?>
            <a href="?" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Tag Cloud -->
<?php if ($tag_counts): ?>
<div style="margin-bottom:1.5rem; display:flex; flex-wrap:wrap; gap:0.5rem;">
    <?php foreach (array_slice($tag_counts, 0, 15, true) as $t => $count): ?>
        <a href="?tag=<?= urlencode($t) ?>"
           class="filter-tab <?= $tag === $t ? 'active' : '' ?>">
            <?= htmlspecialchars($t) ?> <span style="opacity:0.7">(<?= $count ?>)</span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Results -->
<?php if (mysqli_num_rows($results) === 0): ?>
    <div class="card">
        <div class="empty-state">
            <p>No resolutions found<?= $search ? " for \"$search\"" : '' ?>. <?= !$search ? 'Resolutions appear here once requests are resolved.' : '' ?></p>
        </div>
    </div>
<?php else: ?>
    <?php while ($res = mysqli_fetch_assoc($results)): ?>
        <div class="card kb-card">
            <div style="padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.75rem;">
                    <h3 style="font-size:1rem; margin:0;"><?= htmlspecialchars($res['title']) ?></h3>
                    <span style="font-size:0.8rem; color:var(--muted);">
                        <?= date('M j, Y', strtotime($res['created_at'])) ?>
                    </span>
                </div>

                <div style="font-size:0.85rem; color:var(--muted); margin-bottom:0.75rem;">
                    Submitted by <?= htmlspecialchars($res['junior_name']) ?>
                    · Resolved by <?= htmlspecialchars($res['senior_name']) ?>
                </div>

                <div class="kb-issue">
                    <strong>Issue:</strong> <?= htmlspecialchars($res['description']) ?>
                </div>

                <div class="kb-resolution">
                    <strong>✅ Resolution:</strong><br>
                    <?= nl2br(htmlspecialchars($res['resolution_note'])) ?>
                </div>

                <?php if ($res['tags']): ?>
                    <div style="margin-top:0.75rem; display:flex; flex-wrap:wrap; gap:0.4rem;">
                        <?php foreach (explode(',', $res['tags']) as $t): ?>
                            <a href="?tag=<?= urlencode(trim($t)) ?>" class="tag-pill">
                                <?= htmlspecialchars(trim($t)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>