<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$currentUser = require_auth();
$requestId = (int) ($_GET['id'] ?? 0);

$request = fetch_one(
    'SELECT
        cr.*,
        u.full_name AS client_name
     FROM client_requests cr
     INNER JOIN users u ON u.id = cr.client_user_id
     WHERE cr.id = :id
     LIMIT 1',
    ['id' => $requestId]
);

if (!$request) {
    set_flash('error', 'Solicitud no encontrada.');
    redirect($currentUser['role'] === 'provider' ? 'browse-requests.php' : 'client.php');
}

if ($currentUser['role'] === 'provider' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $deliveryDays = trim($_POST['delivery_days'] ?? '');

    if ($message === '' || $amount === '') {
        set_flash('error', 'Completa mensaje y monto.');
        redirect('request-detail.php?id=' . $requestId);
    }

    $existingQuote = fetch_one(
        'SELECT id FROM quotes WHERE request_id = :request_id AND provider_user_id = :provider_user_id LIMIT 1',
        [
            'request_id' => $requestId,
            'provider_user_id' => $currentUser['id'],
        ]
    );

    if ($existingQuote) {
        $stmt = db()->prepare(
            'UPDATE quotes
             SET client_user_id = :client_user_id, message = :message, amount = :amount,
                 delivery_days = :delivery_days, status = "pending", updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'client_user_id' => $request['client_user_id'],
            'message' => $message,
            'amount' => $amount,
            'delivery_days' => $deliveryDays !== '' ? $deliveryDays : null,
            'id' => $existingQuote['id'],
        ]);
        set_flash('success', 'Cotizacion actualizada.');
    } else {
        $stmt = db()->prepare(
            'INSERT INTO quotes
             (request_id, provider_user_id, client_user_id, message, amount, delivery_days, status)
             VALUES (:request_id, :provider_user_id, :client_user_id, :message, :amount, :delivery_days, "pending")'
        );
        $stmt->execute([
            'request_id' => $requestId,
            'provider_user_id' => $currentUser['id'],
            'client_user_id' => $request['client_user_id'],
            'message' => $message,
            'amount' => $amount,
            'delivery_days' => $deliveryDays !== '' ? $deliveryDays : null,
        ]);
        set_flash('success', 'Cotizacion enviada.');
    }

    redirect('request-detail.php?id=' . $requestId);
}

if ($currentUser['role'] === 'client' && $currentUser['id'] !== (int) $request['client_user_id']) {
    set_flash('error', 'No puedes ver esta solicitud.');
    redirect('client.php');
}

$quotes = fetch_rows(
    'SELECT
        q.id,
        q.message,
        q.amount,
        q.delivery_days,
        q.status,
        q.created_at,
        u.full_name AS provider_name
     FROM quotes q
     INNER JOIN users u ON u.id = q.provider_user_id
     WHERE q.request_id = :request_id
     ORDER BY q.created_at DESC',
    ['request_id' => $requestId]
);

$myQuote = null;
if ($currentUser['role'] === 'provider') {
    $myQuote = fetch_one(
        'SELECT * FROM quotes WHERE request_id = :request_id AND provider_user_id = :provider_user_id LIMIT 1',
        ['request_id' => $requestId, 'provider_user_id' => $currentUser['id']]
    );
}

render_header('Detalle de solicitud');
?>
<section class="card">
  <h1><?= e($request['title']) ?></h1>
  <p class="muted">Cliente: <?= e($request['client_name']) ?></p>
  <p><?= e($request['description']) ?></p>
  <div class="meta">
    <span class="chip"><?= e($request['category']) ?></span>
    <span class="chip"><?= e($request['status']) ?></span>
    <span class="chip"><?= e($request['urgency']) ?></span>
    <span class="chip"><?= e($request['country'] ?: 'Pais no definido') ?></span>
    <?php if ($request['budget_min'] !== null || $request['budget_max'] !== null): ?>
      <span class="chip">
        Presupuesto <?= e((string) ($request['budget_min'] ?? '0')) ?> - <?= e((string) ($request['budget_max'] ?? '0')) ?>
      </span>
    <?php endif; ?>
  </div>
</section>
<?php if ($currentUser['role'] === 'provider'): ?>
  <section class="grid two">
    <section class="card">
      <h2>Enviar cotizacion</h2>
      <form method="post">
        <label>
          Monto
          <input type="number" step="0.01" name="amount" value="<?= e((string) ($myQuote['amount'] ?? '')) ?>" required>
        </label>
        <label>
          Dias de entrega
          <input type="number" name="delivery_days" value="<?= e((string) ($myQuote['delivery_days'] ?? '')) ?>">
        </label>
        <label>
          Mensaje
          <textarea name="message" rows="7" required><?= e($myQuote['message'] ?? '') ?></textarea>
        </label>
        <div class="toolbar">
          <button type="submit"><?= $myQuote ? 'Actualizar cotizacion' : 'Enviar cotizacion' ?></button>
          <a class="button secondary" href="browse-requests.php">Volver</a>
        </div>
      </form>
    </section>
    <section class="card">
      <h2>Otras cotizaciones</h2>
      <?php if (!$quotes): ?>
        <p class="muted">Aun no hay cotizaciones enviadas.</p>
      <?php else: ?>
        <div class="list">
          <?php foreach ($quotes as $quote): ?>
            <article class="item">
              <h4><?= e($quote['provider_name']) ?></h4>
              <p><?= e($quote['message']) ?></p>
              <div class="meta">
                <span class="chip">Monto <?= e((string) $quote['amount']) ?></span>
                <?php if ($quote['delivery_days'] !== null): ?>
                  <span class="chip"><?= e((string) $quote['delivery_days']) ?> dias</span>
                <?php endif; ?>
                <span class="chip"><?= e($quote['status']) ?></span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </section>
<?php else: ?>
  <section class="card">
    <h2>Cotizaciones recibidas</h2>
    <?php if (!$quotes): ?>
      <p class="muted">Aun no recibiste cotizaciones para esta solicitud.</p>
    <?php else: ?>
      <div class="list">
        <?php foreach ($quotes as $quote): ?>
          <article class="item">
            <h4><?= e($quote['provider_name']) ?></h4>
            <p><?= e($quote['message']) ?></p>
            <div class="meta">
              <span class="chip">Monto <?= e((string) $quote['amount']) ?></span>
              <?php if ($quote['delivery_days'] !== null): ?>
                <span class="chip"><?= e((string) $quote['delivery_days']) ?> dias</span>
              <?php endif; ?>
              <span class="chip"><?= e($quote['status']) ?></span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="toolbar">
      <a class="button secondary" href="client.php">Volver al panel</a>
    </div>
  </section>
<?php endif; ?>
<?php render_footer(); ?>
