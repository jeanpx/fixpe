<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('client');
$plan = plan_name_for_user($user['id']) ?? 'Sin plan';
$stats = [
    'requests' => count_rows('SELECT COUNT(*) FROM client_requests WHERE client_user_id = :client_user_id', ['client_user_id' => $user['id']]),
    'quotes' => count_rows('SELECT COUNT(*) FROM quotes WHERE client_user_id = :client_user_id', ['client_user_id' => $user['id']]),
    'direct_requests' => count_rows('SELECT COUNT(*) FROM direct_requests WHERE client_user_id = :client_user_id', ['client_user_id' => $user['id']]),
];
$requests = fetch_rows(
    'SELECT id, title, category, status, created_at
     FROM client_requests
     WHERE client_user_id = :client_user_id
     ORDER BY id DESC',
    ['client_user_id' => $user['id']]
);
$quotes = fetch_rows(
    'SELECT
        q.amount,
        q.delivery_days,
        q.status,
        q.created_at,
        cr.id AS request_id,
        cr.title,
        u.full_name AS provider_name
     FROM quotes q
     INNER JOIN client_requests cr ON cr.id = q.request_id
     INNER JOIN users u ON u.id = q.provider_user_id
     WHERE q.client_user_id = :client_user_id
     ORDER BY q.created_at DESC
     LIMIT 8',
    ['client_user_id' => $user['id']]
);
$directRequests = fetch_rows(
    'SELECT
        dr.id,
        dr.subject,
        dr.status,
        dr.budget,
        dr.quoted_amount,
        dr.quoted_delivery_days,
        dr.created_at,
        u.full_name AS provider_name
     FROM direct_requests dr
     INNER JOIN users u ON u.id = dr.provider_user_id
     WHERE dr.client_user_id = :client_user_id
     ORDER BY dr.created_at DESC
     LIMIT 8',
    ['client_user_id' => $user['id']]
);
$firstName = trim((string) strtok((string) ($user['full_name'] ?? ''), ' '));
$displayName = $firstName !== '' ? $firstName : 'cliente';
$hasActivity = $stats['requests'] > 0 || $stats['quotes'] > 0 || $stats['direct_requests'] > 0;

render_header('Panel cliente', !$hasActivity ? 'body-dashboard-empty' : '', 'client-dashboard-main');
?>
<section class="card dashboard-hero">
  <p class="eyebrow">Cliente</p>
  <h1>Hola, <?= e($displayName) ?></h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
  <div class="toolbar">
    <a class="button" href="<?= e(route_url('create-request.php')) ?>">Publicar solicitud</a>
    <a class="button secondary" href="<?= e(route_url('explore-providers.php')) ?>">Explorar especialistas</a>
  </div>
</section>

<section class="stats compact-stats">
  <article>
    <strong><?= e((string) $stats['requests']) ?></strong>
    <p class="muted">Solicitudes</p>
  </article>
  <article>
    <strong><?= e((string) $stats['quotes']) ?></strong>
    <p class="muted">Cotizaciones</p>
  </article>
  <article>
    <strong><?= e((string) $stats['direct_requests']) ?></strong>
    <p class="muted">Directas</p>
  </article>
</section>

<?php if ($requests): ?>
  <section class="card">
    <h2>Mis solicitudes</h2>
    <div class="list">
      <?php foreach ($requests as $request): ?>
        <article class="item">
          <strong><?= e($request['title']) ?></strong>
          <p class="muted"><?= e($request['category']) ?> | <?= e($request['status']) ?> | <?= e($request['created_at']) ?></p>
          <div class="toolbar">
            <a class="button secondary" href="<?= e(route_url('request-detail.php?id=' . (string) $request['id'])) ?>">Ver detalle</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($quotes || $directRequests): ?>
  <section class="grid two">
    <?php if ($quotes): ?>
      <section class="card">
        <h2>Últimas cotizaciones</h2>
        <div class="list">
          <?php foreach ($quotes as $quote): ?>
            <article class="item">
              <h4><?= e($quote['title']) ?></h4>
              <p class="muted"><?= e($quote['provider_name']) ?> | <?= e($quote['status']) ?></p>
              <div class="meta">
                <span class="chip">Monto <?= e((string) $quote['amount']) ?></span>
                <?php if ($quote['delivery_days'] !== null): ?>
                  <span class="chip"><?= e((string) $quote['delivery_days']) ?> días</span>
                <?php endif; ?>
              </div>
              <div class="toolbar">
                <a class="button secondary" href="<?= e(route_url('request-detail.php?id=' . (string) $quote['request_id'])) ?>">Abrir</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($directRequests): ?>
      <section class="card">
        <h2>Solicitudes directas</h2>
        <div class="list">
          <?php foreach ($directRequests as $directRequest): ?>
            <article class="item">
              <h4><?= e($directRequest['subject']) ?></h4>
              <p class="muted"><?= e($directRequest['provider_name']) ?> | <?= e($directRequest['status']) ?></p>
              <div class="meta">
                <?php if ($directRequest['budget'] !== null): ?>
                  <span class="chip">Presupuesto <?= e((string) $directRequest['budget']) ?></span>
                <?php endif; ?>
                <?php if ($directRequest['quoted_amount'] !== null): ?>
                  <span class="chip">Respuesta <?= e((string) $directRequest['quoted_amount']) ?></span>
                <?php endif; ?>
                <?php if ($directRequest['quoted_delivery_days'] !== null): ?>
                  <span class="chip"><?= e((string) $directRequest['quoted_delivery_days']) ?> días</span>
                <?php endif; ?>
              </div>
              <div class="toolbar">
                <a class="button secondary" href="<?= e(route_url('direct-request-detail.php?id=' . (string) $directRequest['id'])) ?>">Abrir</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php render_footer(); ?>
