<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$app_name = APP_NAME ?? 'TekTool';
$role     = $_SESSION['role'] ?? '';
$name     = $_SESSION['full_name'] ?? '';

$nav_links = [
    'junior' => [
        ['label' => 'Dashboard',    'href' => '/junior/dashboard.php'],
        ['label' => 'New Request',  'href' => '/junior/submit_request.php'],
        ['label' => 'My Requests',  'href' => '/junior/my_requests.php'],
    ],
    'senior' => [
        ['label' => 'Dashboard',    'href' => '/senior/dashboard.php'],
        ['label' => 'Availability', 'href' => '/senior/set_availability.php'],
    ],
    'admin' => [
        ['label' => 'Dashboard',    'href' => '/admin/dashboard.php'],
        ['label' => 'Manage Users', 'href' => '/admin/manage_users.php'],
        ['label' => 'Knowledge Base','href' => '/knowledge_base/view_resolutions.php'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app_name ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a href="/<?= $role ?>/dashboard.php" class="nav-brand">⚙️ TekTool</a>

        <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

        <div class="nav-menu" id="navMenu">
            <?php foreach (($nav_links[$role] ?? []) as $link): ?>
                <a href="<?= $link['href'] ?>" class="nav-link
                    <?= ($_SERVER['REQUEST_URI'] === $link['href']) ? 'active' : '' ?>">
                    <?= $link['label'] ?>
                </a>
            <?php endforeach; ?>
            <a href="/auth/logout.php" class="nav-link nav-logout">Logout</a>
        </div>

        <div class="nav-user">
            <?php $role_labels = ['junior' => 'Field Tech', 'senior' => 'Lead Tech', 'admin' => 'Admin']; ?>
<span class="badge badge-<?= $role ?>"><?= $role_labels[$role] ?? ucfirst($role) ?></span>
            <span class="nav-name"><?= htmlspecialchars($name) ?></span>
        </div>
    </div>
</nav>
<main class="main-content">