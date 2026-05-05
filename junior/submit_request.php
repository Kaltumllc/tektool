<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('junior');

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');

    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } else {
        // Find first available senior
        $senior = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT u.id FROM users u
             JOIN availability a ON u.id = a.senior_id
             WHERE u.role = 'senior' AND a.is_available = 1
             ORDER BY a.updated_at ASC
             LIMIT 1"
        ));

        $senior_id = $senior['id'] ?? null;
        $status    = $senior_id ? 'in_progress' : 'open';

        $stmt = mysqli_prepare($conn,
            "INSERT INTO help_requests (junior_id, senior_id, title, description, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'iisss',
            $user_id, $senior_id, $title, $description, $status
        );

        if (mysqli_stmt_execute($stmt)) {
            $request_id = mysqli_insert_id($conn);

            // Audit log
            $action = "New request #$request_id submitted by junior #$user_id";
            $log = mysqli_prepare($conn,
                "INSERT INTO audit_log (user_id, action) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($log, 'is', $user_id, $action);
            mysqli_stmt_execute($log);

            if ($senior_id) {
                $success = "Request submitted and assigned to a senior technician!";
            } else {
                $success = "Request submitted! We'll assign a senior technician shortly.";
            }
        } else {
            $error = 'Failed to submit request. Please try again.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Submit Help Request</h1>
    <a href="/junior/dashboard.php" class="btn btn-outline btn-sm">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <a href="/junior/my_requests.php"> View my requests →</a>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 680px;">
    <div class="card-header"><h2>Describe Your Issue</h2></div>
    <div style="padding: 1.5rem;">
        <form method="POST" action="">
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">Select a category</option>
                    <option value="electrical">Electrical</option>
                    <option value="hvac">HVAC</option>
                    <option value="plumbing">Plumbing</option>
                    <option value="it_network">IT / Network</option>
                    <option value="mechanical">Mechanical</option>
                    <option value="safety">Safety</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Issue Title <span style="color:var(--danger)">*</span></label>
                <input type="text" name="title"
                       placeholder="e.g. HVAC unit not cooling on floor 3"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       required>
            </div>
            <div class="form-group">
                <label>Describe the Issue <span style="color:var(--danger)">*</span></label>
                <textarea name="description" rows="5"
                    placeholder="Provide as much detail as possible — what you tried, error codes, location, equipment model..."
                    required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Location / Site</label>
                <input type="text" name="location"
                       placeholder="e.g. Building A, Floor 2, Room 214"
                       value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
            </div>
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="/junior/dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>