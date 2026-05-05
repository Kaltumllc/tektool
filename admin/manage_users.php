<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('admin');

$message = '';
$error   = '';

// Toggle availability for senior
if (isset($_GET['toggle_avail']) && is_numeric($_GET['toggle_avail'])) {
    $sid  = (int)$_GET['toggle_avail'];
    $curr = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT is_available FROM availability WHERE senior_id = $sid"
    ));
    if ($curr) {
        $new = $curr['is_available'] ? 0 : 1;
        mysqli_query($conn, "UPDATE availability SET is_available = $new WHERE senior_id = $sid");
        $message = 'Availability updated.';
    }
}

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
        $message = 'User removed.';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Get all users with stats
$users = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.role, u.created_at,
           a.is_available,
           COUNT(DISTINCT hr.id) as total_requests
    FROM users u
    LEFT JOIN availability a ON u.id = a.senior_id
    LEFT JOIN help_requests hr ON (u.id = hr.junior_id OR u.id = hr.senior_id)
    GROUP BY u.id
    ORDER BY u.role, u.created_at DESC
");

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <a href="/auth/register.php" target="_blank" class="btn btn-primary btn-sm">+ Add User</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Requests</th>
                    <th>Availability</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                    <td style="color:var(--muted); font-size:0.88rem;"><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                    <?php $role_labels = ['junior' => 'Field Tech', 'senior' => 'Lead Tech', 'admin' => 'Admin']; ?>
                    <span class="badge badge-<?= $user['role'] ?>"><?= $role_labels[$user['role']] ?? ucfirst($user['role']) ?></span>
                    </td>
                    <td><?= $user['total_requests'] ?></td>
                    <td>
                        <?php if ($user['role'] === 'senior'): ?>
                            <a href="?toggle_avail=<?= $user['id'] ?>"
                               class="status-badge <?= $user['is_available'] ? 'status-resolved' : 'status-open' ?>">
                                <?= $user['is_available'] ? 'Available' : 'Unavailable' ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.85rem; color:var(--muted);">
                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                    </td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <a href="?delete=<?= $user['id'] ?>"
                               onclick="return confirm('Remove <?= htmlspecialchars($user['full_name']) ?>?')"
                               style="color:var(--danger); font-size:0.85rem; text-decoration:none;">
                                Remove
                            </a>
                        <?php else: ?>
                            <span style="color:var(--muted); font-size:0.85rem;">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>