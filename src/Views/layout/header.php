<?php
// Resolve the current user for the nav bar
$_navUserId   = Session::userId();
$_navUser     = $_navUserId ? User::findById($_navUserId) : null;
$_currentPath = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?><?= e(Lang::t('app.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="stylesheet" href="/assets/css/app.css">
    <!-- Anti-flash: apply saved theme before first paint -->
    <script>
    (function(){
      var t = localStorage.getItem('letsgo_theme');
      if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
    </script>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand"><?= e(Lang::t('app.name')) ?></a>

        <?php if ($_navUser): ?>
        <nav class="nav-main">
            <?php if ((int)($_navUser['is_admin'] ?? 0) === 1): ?>
            <a href="/admin/settings" class="btn-ghost <?= str_starts_with($_currentPath, '/admin') ? 'active' : '' ?>">
                <?= e(Lang::t('nav.admin')) ?>
            </a>
            <?php endif; ?>
            <a href="/profile/password" class="nav-username btn-ghost <?= str_starts_with($_currentPath, '/profile') ? 'active' : '' ?>">
                <?= e($_navUser['username'] ?? $_navUser['email']) ?>
            </a>
            <form method="POST" action="/logout" class="inline-form">
                <button type="submit" class="btn-ghost"><?= e(Lang::t('nav.logout')) ?></button>
            </form>
        </nav>
        <?php else: ?>
        <nav class="nav-main">
            <a href="/login"    class="btn-ghost <?= $_currentPath === '/login'    ? 'active' : '' ?>"><?= e(Lang::t('nav.login')) ?></a>
            <a href="/register" class="btn-ghost <?= $_currentPath === '/register' ? 'active' : '' ?>"><?= e(Lang::t('nav.register')) ?></a>
        </nav>
        <?php endif; ?>

        <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" aria-label="Téma váltása" title="Téma váltása">
            <span id="themeIcon"></span>
        </button>
    </div>
</header>

<main class="site-main">
    <div class="container">
