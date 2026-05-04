<?php
require_once '../includes/auth_guard.php';
session_start();
session_unset();
session_destroy();
header('Location: /tektool/auth/login.php');
exit();
?>