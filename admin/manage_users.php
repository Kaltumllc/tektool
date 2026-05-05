<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('admin');

$message = '';
$error   = '';

$msg_code = $_GET['msg'] ?? '';
$err_code = $_GET['error'] ?? '';

if ($msg_code === 'promoted') {
    $name     = htmlspecialchars($_GET['name'] ?? 'User');
    $new_role = htmlspecialchars($_GET['role'] ?? '');
    $role_labels_flash = ['junior' => 'Field Tech', 'senior' => 'Lead Tech', 'admin' => 'Admin'];
    $label = $role_labels_flash[$new_role] ?? ucfirst($new_role);
    $message = "$name has been updated to <strong>$label</strong>.";
} elseif ($msg_code === 'nochange') {
    $message = 'No change — user already has that role.';
}

if ($err_code === 'self')         $error = 'You cannot change your own role.';
elseif ($err_code === 'invalid')  $error = 'Invalid user or role selection.';
elseif ($err_code === 'notfound') $error = 'User not found.';
elseif ($err_code === 'dbfail')   $error = 'Database error — role not updated.';

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

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
        $message = 'User removed.';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// FIX: full GROUP BY for TiDB strict only_full_group_by mode
$users = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.role, u.created_at,
           a.is_available,
           COUNT(DISTINCT hr.id) as total_requests
    FROM users u
    LEFT JOIN availability a ON u.id = a.senior_id
    LEFT JOIN help_requests hr ON (u.id = hr.junior_id OR u.id = hr.senior_id)
    GROUP BY u.id, u.full_name, u.email, u.role, u.created_at, a.is_available
    ORDER BY u.role, u.created_at DESC
");

$role_labels = ['junior' => 'Field Tech', 'senior' => 'Lead Tech', 'admin' => 'Admin'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <a href="/auth/register.php" target="_blank" class="btn btn-primary btn-sm">+ Add User</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
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
                    <th>Change Role</th>
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
                        <span class="badge badge-<?= $user['role'] ?>">
                            <?= $role_labels[$user['role']] ?? ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" action="/admin/promote_user.php"
                              style="display:flex; gap:0.4rem; align-items:center;"
                              onsubmit="return confirm('Change role for <?= htmlspecialchars($user['full_name']) ?>?')">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="new_role"
                                    style="padding:0.3rem 0.5rem; font-size:0.82rem;
                                           border:1px solid var(--border); border-radius:var(--radius);
                                           background:var(--bg); color:var(--text);">
                                <option value="junior" <?= $user['role']==='junior' ? 'selected':'' ?>>Field Tech</option>
                                <option value="senior" <?= $user['role']==='senior' ? 'selected':'' ?>>Lead Tech</option>
                                <option value="admin"  <?= $user['role']==='admin'  ? 'selected':'' ?>>Admin</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline"
                                    style="padding:0.25rem 0.6rem; font-size:0.78rem;">Save</button>
                        </form>
                        <?php else: ?>
                            <span style="color:var(--muted); font-size:0.82rem;">— you —</span>
                        <?php endif; ?>
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
                               onclick="return confirm('Remove <?= htmlspecialchars($user['full_name']) ?>? Cannot be undone.')"
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