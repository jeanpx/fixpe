<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('provider');
$plan = plan_name_for_user($user['id']) ?? 'Sin plan';
$services = fetch_rows(
    'SELECT title, category, price_from, created_at
     FROM services
     WHERE provider_user_id = :provider_user_id
     ORDER BY id DESC',
    ['provider_user_id' => $user['id']]
);
$profile = fetch_rows(
    'SELECT headline, verified, availability_status
     FROM provider_profiles
     WHERE user_id = :user_id
     LIMIT 1',
    ['user_id' => $user['id']]
);
$profileRow = $profile[0] ?? null;

render_header('Panel proveedor');
?>
<section class="card">
  <h1>Panel proveedor</h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
  <?php if ($profileRow): ?>
    <p class="muted">
      Perfil: <?= e($profileRow['headline']) ?> |
      Estado: <?= e($profileRow['availability_status']) ?> |
      Verificado: <?= (int) $profileRow['verified'] === 1 ? 'si' : 'no' ?>
    </p>
  <?php endif; ?>
  <div class="hero-actions">
    <a class="button" href="create-service.php">Publicar servicio</a>
  </div>
</section>
<section class="card">
  <h2>Mis servicios</h2>
  <?php if (!$services): ?>
    <p class="muted">Aun no has publicado servicios.</p>
  <?php else: ?>
    <div class="list">
      <?php foreach ($services as $service): ?>
        <article class="item">
          <strong><?= e($service['title']) ?></strong>
          <p class="muted">
            <?= e($service['category']) ?> |
            Desde <?= e((string) $service['price_from']) ?> |
            <?= e($service['created_at']) ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
