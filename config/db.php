<?php
// Use Environment Variables for production (Render/Aiven) 
// and fall back to local settings for XAMPP
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'tektool');
define('APP_NAME', 'TekTool');

// The secret key is now pulled from the server's environment settings
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(json_encode([
        'error' => 'Database connection failed: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');
?>