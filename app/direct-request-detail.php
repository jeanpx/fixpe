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

if ($user['role'] === 'provider' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quotedAmount = trim($_POST['quoted_amount'] ?? '');
    $quotedDeliveryDays = trim($_POST['quoted_delivery_days'] ?? '');
    $providerResponse = trim($_POST['provider_response'] ?? '');
    $status = $_POST['status'] ?? 'reviewed';

    if ($quotedAmount === '' || $providerResponse === '') {
        set_flash('error', 'Completa monto y mensaje de respuesta.');
        redirect('direct-request-detail.php?id=' . $directRequestId);
    }

    if (!in_array($status, ['reviewed', 'accepted', 'rejected', 'closed'], true)) {
        $status = 'reviewed';
    }

    $stmt = db()->prepare(
        'UPDATE direct_requests
         SET quoted_amount = :quoted_amount,
             quoted_delivery_days = :quoted_delivery_days,
             provider_response = :provider_response,
             status = :status,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'quoted_amount' => $quotedAmount,
        'quoted_delivery_days' => $quotedDeliveryDays !== '' ? $quotedDeliveryDays : null,
        'provider_response' => $providerResponse,
        'status' => $status,
        'id' => $directRequestId,
    ]);

    set_flash('success', 'Respuesta guardada.');
    redirect('direct-request-detail.php?id=' . $directRequestId);
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
</section>
<?php if ($user['role'] === 'provider'): ?>
  <section class="grid two">
    <section class="card">
      <h2>Responder solicitud</h2>
      <form method="post">
        <label>
          Monto
          <input type="number" step="0.01" name="quoted_amount" value="<?= e((string) ($directRequest['quoted_amount'] ?? '')) ?>" required>
        </label>
        <label>
          Días de entrega
          <input type="number" name="quoted_delivery_days" value="<?= e((string) ($directRequest['quoted_delivery_days'] ?? '')) ?>">
        </label>
        <label>
          Estado
          <select name="status">
            <option value="reviewed" <?= $directRequest['status'] === 'reviewed' ? 'selected' : '' ?>>Revisado</option>
            <option value="accepted" <?= $directRequest['status'] === 'accepted' ? 'selected' : '' ?>>Aceptado</option>
            <option value="rejected" <?= $directRequest['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
            <option value="closed" <?= $directRequest['status'] === 'closed' ? 'selected' : '' ?>>Cerrado</option>
          </select>
        </label>
        <label>
          Mensaje
          <textarea name="provider_response" rows="8" required><?= e($directRequest['provider_response'] ?? '') ?></textarea>
        </label>
        <div class="toolbar">
          <button type="submit"><?= $directRequest['quoted_amount'] !== null ? 'Actualizar respuesta' : 'Enviar respuesta' ?></button>
          <a class="button secondary" href="provider.php">Volver</a>
        </div>
      </form>
    </section>
    <section class="card">
      <h2>Resumen de respuesta</h2>
      <?php if ($directRequest['quoted_amount'] === null && !$directRequest['provider_response']): ?>
        <p class="muted">Aún no respondiste esta solicitud.</p>
      <?php else: ?>
        <article class="item">
          <h4><?= e($directRequest['provider_name']) ?></h4>
          <p><?= e($directRequest['provider_response'] ?? '') ?></p>
          <div class="meta">
            <?php if ($directRequest['quoted_amount'] !== null): ?>
              <span class="chip">Monto <?= e((string) $directRequest['quoted_amount']) ?></span>
            <?php endif; ?>
            <?php if ($directRequest['quoted_delivery_days'] !== null): ?>
              <span class="chip"><?= e((string) $directRequest['quoted_delivery_days']) ?> días</span>
            <?php endif; ?>
            <span class="chip"><?= e($directRequest['status']) ?></span>
          </div>
        </article>
      <?php endif; ?>
    </section>
  </section>
<?php else: ?>
  <section class="card">
    <h2>Respuesta del especialista</h2>
    <?php if ($directRequest['quoted_amount'] === null && !$directRequest['provider_response']): ?>
      <p class="muted">Aún no recibiste respuesta del partner.</p>
    <?php else: ?>
      <article class="item">
        <h4><?= e($directRequest['provider_name']) ?></h4>
        <p><?= e($directRequest['provider_response'] ?? '') ?></p>
        <div class="meta">
          <?php if ($directRequest['quoted_amount'] !== null): ?>
            <span class="chip">Monto <?= e((string) $directRequest['quoted_amount']) ?></span>
          <?php endif; ?>
          <?php if ($directRequest['quoted_delivery_days'] !== null): ?>
            <span class="chip"><?= e((string) $directRequest['quoted_delivery_days']) ?> días</span>
          <?php endif; ?>
          <span class="chip"><?= e($directRequest['status']) ?></span>
        </div>
      </article>
    <?php endif; ?>
    <div class="toolbar">
      <a class="button secondary" href="client.php">Volver</a>
    </div>
  </section>
<?php endif; ?>
<?php render_footer(); ?>
