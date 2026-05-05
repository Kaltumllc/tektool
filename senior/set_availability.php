<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('senior');

header('Location: /senior/dashboard.php');
exit();
?>