<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_login();

header('Content-Type: application/json');

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if (!$request_id) {
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

// Verify user belongs to this request
$stmt = mysqli_prepare($conn,
    "SELECT id FROM help_requests WHERE id = ? AND (junior_id = ? OR senior_id = ?)"
);
mysqli_stmt_bind_param($stmt, 'iii', $request_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    echo json_encode(['error' => 'Unauthorized.']);
    exit();
}

if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No video file received.']);
    exit();
}

$file      = $_FILES['video'];
$max_size  = 100 * 1024 * 1024; // 100MB
$allowed   = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];

if ($file['size'] > $max_size) {
    echo json_encode(['error' => 'File too large. Max 100MB.']);
    exit();
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimetype = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimetype, $allowed)) {
    echo json_encode(['error' => 'Invalid file type. Use MP4, WebM, or MOV.']);
    exit();
}

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/tektool/uploads/videos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'req' . $request_id . '_' . $user_id . '_' . time() . '.' . $ext;
$dest     = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $orig = $file['name'];
    $size = $file['size'];

    $stmt2 = mysqli_prepare($conn,
        "INSERT INTO videos (request_id, uploader_id, filename, original_name, file_size)
         VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt2, 'iissi',
        $request_id, $user_id, $filename, $orig, $size
    );
    mysqli_stmt_execute($stmt2);

    echo json_encode([
        'success'  => true,
        'filename' => $filename,
        'url'      => '/tektool/uploads/videos/' . $filename
    ]);
} else {
    echo json_encode(['error' => 'Upload failed. Check folder permissions.']);
}
?>