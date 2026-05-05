<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('admin');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: dashboard.php'); exit(); }

// Handle the UPDATE (The "Write" Operation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $stmt = mysqli_prepare($conn, "UPDATE help_requests SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log the Admin override in the Audit Log
        $action = "Admin #{$_SESSION['user_id']} forced status update to $new_status on Request #$id";
        $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
        mysqli_stmt_bind_param($log_stmt, 'is', $_SESSION['user_id'], $action);
        mysqli_stmt_execute($log_stmt);
        
        $msg = "Request updated successfully.";
    }
}

// Fetch current request data
$res = mysqli_query($conn, "SELECT * FROM help_requests WHERE id = $id");
$req = mysqli_fetch_assoc($res);

require_once '../includes/header.php';
?>

<div class="card">
    <h2>Manage Request #<?= $id ?></h2>
    <form method="POST">
        <div class="form-group">
            <label>Current Status</label>
            <select name="status">
                <option value="pending" <?= $req['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="open" <?= $req['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                <option value="in_progress" <?= $req['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $req['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Request</button>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </form>
</div>