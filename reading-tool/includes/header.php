<?php
require_once __DIR__ . '/auth.php';

// Use the same reliable base path function from auth.php
$base = getBasePath();

// Determine active nav item
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ReadEase' : 'ReadEase' ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">

    <!-- PWA -->
    <link rel="manifest" href="<?= $base ?>manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ReadEase">
    <link rel="apple-touch-icon" href="<?= $base ?>assets/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= $base ?>assets/icons/icon-192.png">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📖</text></svg>">
</head>
<body>

<?php if (isLoggedIn()): ?>

<div class="app-layout">

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        <a href="<?= $base ?><?= isAdmin() ? 'admin/dashboard.php' : 'dashboard.php' ?>" class="sidebar-logo">
            <span class="sidebar-logo-icon">📖</span>
            <span class="sidebar-logo-text">ReadEase</span>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Menu</div>

            <?php if (isAdmin()): ?>
                <a href="<?= $base ?>admin/dashboard.php"
                   class="sidebar-link <?= ($currentFile === 'dashboard.php' && $currentDir === 'admin') ? 'active' : '' ?>">
                    <span class="sidebar-icon">📊</span> Dashboard
                </a>
                <a href="<?= $base ?>admin/materials.php"
                   class="sidebar-link <?= ($currentFile === 'materials.php') ? 'active' : '' ?>">
                    <span class="sidebar-icon">📚</span> Materials
                </a>
                <a href="<?= $base ?>admin/recordings.php"
                   class="sidebar-link <?= ($currentFile === 'recordings.php') ? 'active' : '' ?>">
                    <span class="sidebar-icon">🎤</span> Recordings
                </a>
                <a href="<?= $base ?>admin/users.php"
                   class="sidebar-link <?= ($currentFile === 'users.php') ? 'active' : '' ?>">
                    <span class="sidebar-icon">👥</span> Users
                </a>
            <?php else: ?>
                <a href="<?= $base ?>dashboard.php"
                   class="sidebar-link <?= ($currentFile === 'dashboard.php' && $currentDir !== 'admin') ? 'active' : '' ?>">
                    <span class="sidebar-icon">🏠</span> Home
                </a>
                <a href="<?= $base ?>student/my-recordings.php"
                   class="sidebar-link <?= ($currentFile === 'my-recordings.php') ? 'active' : '' ?>">
                    <span class="sidebar-icon">🎧</span> My Recordings
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
                <div>
                    <div class="sidebar-user-name"><?= e(currentUserName()) ?></div>
                    <div class="sidebar-user-role"><?= isAdmin() ? 'Admin' : 'Student' ?></div>
                </div>
            </div>
            <div class="sidebar-actions">
                <a href="<?= $base ?>profile.php" class="btn-profile">👤 Profile</a>
                <a href="<?= $base ?>logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="app-content">
        <div class="app-topbar">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>
                <span class="app-topbar-title"><?= isset($pageTitle) ? e($pageTitle) : 'ReadEase' ?></span>
            </div>
            <div class="app-topbar-right">
                <div class="user-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
            </div>
        </div>

        <main class="app-main">
<?php
// Display flash messages
$flash = getFlash();
if ($flash):
?>
        <div class="container" style="padding-top:1.25rem;">
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        </div>
<?php endif; ?>

<?php else: ?>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $base ?>index.php" class="logo">
            <span class="logo-icon">📖</span>
            <span class="logo-text">ReadEase</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav">
            <a href="<?= $base ?>index.php" class="nav-link <?= ($currentFile === 'index.php') ? 'active' : '' ?>">Home</a>
            <a href="<?= $base ?>login.php" class="btn btn-sm btn-primary">Login</a>
        </nav>
    </div>
</header>

<main class="main-content">
<?php
// Display flash messages
$flash = getFlash();
if ($flash):
?>
<div class="container">
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>
