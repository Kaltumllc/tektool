<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_login();

$search = trim($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($search) {
    // FIX: use resolution_text not resolution_note
    $where   .= " AND (r.resolution_text LIKE ? OR hr.title LIKE ? OR hr.description LIKE ?)";
    $term     = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types   .= 'sss';
}

// FIX: use resolution_text, resolved_by; removed tags (not in schema)
$sql = "
    SELECT r.id, r.resolution_text, r.ai_summary, r.created_at,
           hr.title, hr.description,
           j.full_name as junior_name,
           s.full_name as senior_name
    FROM resolutions r
    JOIN help_requests hr ON r.request_id = hr.id
    JOIN users j ON hr.junior_id = j.id
    JOIN users s ON r.resolved_by = s.id
    $where
    ORDER BY r.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$results = mysqli_stmt_get_result($stmt);

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
               style="flex:1; min-width:200px; padding:0.65rem 0.85rem;
                      border:1px solid var(--border); border-radius:var(--radius); font-size:1rem;">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="?" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Results -->
<?php if (mysqli_num_rows($results) === 0): ?>
    <div class="card">
        <div class="empty-state">
            <p>No resolutions found<?= $search ? " for \"" . htmlspecialchars($search) . "\"" : '' ?>.
            <?= !$search ? 'Resolutions appear here once requests are resolved.' : '' ?></p>
        </div>
    </div>
<?php else: ?>
    <?php while ($res = mysqli_fetch_assoc($results)): ?>
        <div class="card kb-card">
            <div style="padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;
                            flex-wrap:wrap; gap:0.75rem; margin-bottom:0.75rem;">
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

                <!-- FIX: use resolution_text not resolution_note -->
                <div class="kb-resolution">
                    <strong>✅ Resolution:</strong><br>
                    <?= nl2br(htmlspecialchars($res['resolution_text'])) ?>
                </div>

                <?php if ($res['ai_summary']): ?>
                    <div style="margin-top:0.75rem; padding:0.75rem;
                                background:var(--bg-alt); border-radius:var(--radius);
                                font-size:0.85rem;">
                        <strong>🤖 AI Summary:</strong> <?= htmlspecialchars($res['ai_summary']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>