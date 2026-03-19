<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('client');
$plan = plan_name_for_user($user['id']) ?? 'Sin plan';
$requests = fetch_rows(
    'SELECT title, category, status, created_at
     FROM client_requests
     WHERE client_user_id = :client_user_id
     ORDER BY id DESC',
    ['client_user_id' => $user['id']]
);

render_header('Panel cliente');
?>
<section class="card">
  <h1>Panel cliente</h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
  <div class="hero-actions">
    <a class="button" href="create-request.php">Publicar solicitud</a>
  </div>
</section>
<section class="card">
  <h2>Mis solicitudes</h2>
  <?php if (!$requests): ?>
    <p class="muted">Aun no has publicado solicitudes.</p>
  <?php else: ?>
    <div class="list">
      <?php foreach ($requests as $request): ?>
        <article class="item">
          <strong><?= e($request['title']) ?></strong>
          <p class="muted"><?= e($request['category']) ?> | <?= e($request['status']) ?> | <?= e($request['created_at']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
