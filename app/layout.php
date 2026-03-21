<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function render_header(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    $flash = flash();
    $user = current_user();
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | <?= e(app_name()) ?></title>
  <link rel="stylesheet" href="<?= e(asset_url('styles.css')) ?>">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="<?= e(route_url('index.php')) ?>"><?= e(app_name()) ?></a>
    <nav class="topnav">
      <?php if ($user): ?>
        <a href="<?= e(route_url('dashboard.php')) ?>">Panel</a>
        <a href="<?= e(route_url('logout.php')) ?>">Salir</a>
      <?php else: ?>
        <a href="<?= e(route_url('login.php')) ?>">Ingresar</a>
        <a href="<?= e(route_url('register.php')) ?>">Crear cuenta</a>
      <?php endif; ?>
    </nav>
  </header>
  <main class="container">
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
  </main>
</body>
</html>
<?php
}
