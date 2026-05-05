<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app_name = defined('APP_NAME') ? APP_NAME : 'TekTool';

$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? 'User';

$role_labels = [
    'junior' => 'Field Tech',
    'senior' => 'Lead Tech',
    'admin'  => 'Admin',
];

$role_icons = [
    'junior' => '🧰',
    'senior' => '🛠️',
    'admin'  => '🛡️',
];

$dashboard_href = '/index.php';

if ($role === 'junior') {
    $dashboard_href = '/junior/dashboard.php';
} elseif ($role === 'senior') {
    $dashboard_href = '/senior/dashboard.php';
} elseif ($role === 'admin') {
    $dashboard_href = '/admin/dashboard.php';
}

$nav_links = [
    'junior' => [
        ['label' => 'Dashboard',   'href' => '/junior/dashboard.php',      'icon' => '📊'],
        ['label' => 'New Request', 'href' => '/junior/submit_request.php', 'icon' => '➕'],
        ['label' => 'My Requests', 'href' => '/junior/my_requests.php',    'icon' => '📋'],
    ],
    'senior' => [
        ['label' => 'Dashboard',    'href' => '/senior/dashboard.php',        'icon' => '📊'],
        ['label' => 'Availability', 'href' => '/senior/set_availability.php', 'icon' => '🟢'],
    ],
    'admin' => [
        ['label' => 'Dashboard',      'href' => '/admin/dashboard.php',                 'icon' => '📊'],
        ['label' => 'Manage Users',   'href' => '/admin/manage_users.php',              'icon' => '👥'],
        ['label' => 'Knowledge Base', 'href' => '/knowledge_base/view_resolutions.php', 'icon' => '📚'],
    ],
];

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

$display_role = $role_labels[$role] ?? ucfirst($role ?: 'User');
$display_icon = $role_icons[$role] ?? '👤';
$first_name = trim(explode(' ', $name)[0] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($app_name) ?> — <?= htmlspecialchars($display_role) ?></title>

    <link rel="stylesheet" href="/assets/css/style.css?v=20260505-4">
</head>

<body class="app-page app-role-<?= htmlspecialchars($role) ?>">

<div class="app-shell">

    <nav class="navbar">
        <div class="nav-inner">

            <a href="<?= htmlspecialchars($dashboard_href) ?>" class="nav-brand">
                <span class="brand-icon">⚙️</span>
                <span>TekTool</span>
            </a>

            <button class="nav-toggle" type="button" onclick="toggleNav()" aria-label="Open navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <?php foreach (($nav_links[$role] ?? []) as $link): ?>
                    <?php $is_active = ($current_path === $link['href']); ?>

                    <a href="<?= htmlspecialchars($link['href']) ?>"
                       class="nav-link <?= $is_active ? 'active' : '' ?>">
                        <span class="nav-link-icon"><?= htmlspecialchars($link['icon']) ?></span>
                        <span><?= htmlspecialchars($link['label']) ?></span>
                    </a>
                <?php endforeach; ?>

                <a href="/auth/logout.php" class="nav-link nav-logout">
                    <span class="nav-link-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>

            <div class="nav-user">
                <span class="badge badge-<?= htmlspecialchars($role) ?>">
                    <?= htmlspecialchars($display_icon . ' ' . $display_role) ?>
                </span>

                <span class="nav-name">
                    <?= htmlspecialchars($first_name) ?>
                </span>
            </div>

        </div>
    </nav>

    <main class="main-content">