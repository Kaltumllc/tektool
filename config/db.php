<?php
// --- GLOBAL APPLICATION CONSTANTS ---
define('APP_NAME', 'TekTool');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://tektool.onrender.com');

// --- DATABASE CONFIGURATION ---
$host    = getenv('DB_HOST');
$user    = getenv('DB_USER');
$pass    = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$port    = getenv('DB_PORT') ?: 4000;

// Initialize mysqli
$conn = mysqli_init();

// TiDB Cloud requires SSL
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Establish the connection
if (!mysqli_real_connect($conn, $host, $user, $pass, $db_name, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for professional character support
mysqli_set_charset($conn, "utf8mb4");
?>