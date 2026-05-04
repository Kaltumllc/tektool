<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_login();

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'senior') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data      = json_decode(file_get_contents('php://input'), true);
$available = isset($data['available']) ? (int)$data['available'] : 0;
$user_id   = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
    "UPDATE availability SET is_available = ? WHERE senior_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $available, $user_id);
$ok = mysqli_stmt_execute($stmt);

echo json_encode([
    'success'   => $ok,
    'available' => $available
]);
?>