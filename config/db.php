<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 4000;

// Initialize mysqli
$conn = mysqli_init();

// TiDB Cloud requires SSL. We don't need a specific CA file 
// for most PHP environments on Render as they use the system certs.
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Establish the connection
if (!mysqli_real_connect($conn, $host, $user, $pass, $db_name, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>