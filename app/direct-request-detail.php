<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth();
$directRequestId = (int) ($_GET['id'] ?? 0);

$directRequest = fetch_one(
    'SELECT
        dr.*,
        client.full_name AS client_name,
        client.email AS client_email,
        provider.full_name AS provider_name
     FROM direct_requests dr
     INNER JOIN users client ON client.id = dr.client_user_id
     INNER JOIN users provider ON provider.id = dr.provider_user_id
     WHERE dr.id = :id
     LIMIT 1',
    ['id' => $directRequestId]
);

if (!$directRequest) {
    set_flash('error', 'Solicitud directa no encontrada.');
    redirect($user['role'] === 'provider' ? 'provider.php' : 'client.php');
}

if ($user['role'] === 'provider' && $user['id'] !== (int) $directRequest['provider_user_id']) {
    set_flash('error', 'No puedes ver esta solicitud.');
    redirect('provider.php');
}

if ($user['role'] === 'client' && $user['id'] !== (int) $directRequest['client_user_id']) {
    set_flash('error', 'No puedes ver esta solicitud.');
    redirect('client.php');
}

render_header('Solicitud directa');
?>
<section class="card">
  <h1><?= e($directRequest['subject']) ?></h1>
  <p class="muted">
    Cliente: <?= e($directRequest['client_name']) ?>
    <?php if ($user['role'] === 'provider'): ?>
      | Correo: <?= e($directRequest['client_email']) ?>
    <?php endif; ?>
  </p>
  <p><?= e($directRequest['message']) ?></p>
  <div class="meta">
    <span class="chip"><?= e($directRequest['status']) ?></span>
    <?php if ($directRequest['budget'] !== null): ?>
      <span class="chip">Presupuesto <?= e((string) $directRequest['budget']) ?></span>
    <?php endif; ?>
    <span class="chip"><?= e($directRequest['created_at']) ?></span>
  </div>
  <div class="toolbar">
    <a class="button secondary" href="<?= $user['role'] === 'provider' ? 'provider.php' : 'client.php' ?>">Volver al panel</a>
  </div>
</section>
<?php render_footer(); ?>
