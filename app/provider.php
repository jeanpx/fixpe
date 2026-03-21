<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('provider');
$plan = plan_name_for_user($user['id']) ?? 'Sin plan';
$stats = [
    'services' => count_rows('SELECT COUNT(*) FROM services WHERE provider_user_id = :provider_user_id', ['provider_user_id' => $user['id']]),
    'quotes' => count_rows('SELECT COUNT(*) FROM quotes WHERE provider_user_id = :provider_user_id', ['provider_user_id' => $user['id']]),
    'direct_requests' => count_rows('SELECT COUNT(*) FROM direct_requests WHERE provider_user_id = :provider_user_id', ['provider_user_id' => $user['id']]),
];
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
$openRequests = fetch_rows(
    'SELECT id, title, category, urgency, created_at
     FROM client_requests
     WHERE status IN ("open", "in_review", "matched")
     ORDER BY created_at DESC
     LIMIT 8'
);
$directLeads = fetch_rows(
    'SELECT
        dr.id,
        dr.subject,
        dr.message,
        dr.budget,
        dr.status,
        dr.quoted_amount,
        dr.created_at,
        u.full_name AS client_name
     FROM direct_requests dr
     INNER JOIN users u ON u.id = dr.client_user_id
     WHERE dr.provider_user_id = :provider_user_id
     ORDER BY dr.created_at DESC
     LIMIT 8',
    ['provider_user_id' => $user['id']]
);
$sentQuotes = fetch_rows(
    'SELECT
        q.amount,
        q.status,
        q.created_at,
        cr.id AS request_id,
        cr.title,
        u.full_name AS client_name
     FROM quotes q
     INNER JOIN client_requests cr ON cr.id = q.request_id
     INNER JOIN users u ON u.id = q.client_user_id
     WHERE q.provider_user_id = :provider_user_id
     ORDER BY q.created_at DESC
     LIMIT 8',
    ['provider_user_id' => $user['id']]
);
$firstName = trim((string) strtok((string) ($user['full_name'] ?? ''), ' '));
$displayName = $firstName !== '' ? $firstName : 'proveedor';
$hasServices = !empty($services);
$hasDirectLeads = !empty($directLeads);
$hasSentQuotes = !empty($sentQuotes);
$hasOpenRequests = !empty($openRequests);

render_header('Panel proveedor');
?>
<section class="card dashboard-hero">
  <p class="eyebrow">Proveedor</p>
  <h1>Hola, <?= e($displayName) ?></h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
  <?php if ($profileRow): ?>
    <p class="muted">
      <?= e($profileRow['headline']) ?> | <?= e($profileRow['availability_status']) ?> | <?= (int) $profileRow['verified'] === 1 ? 'Verificado' : 'Sin verificar' ?>
    </p>
  <?php endif; ?>
  <div class="toolbar">
    <a class="button" href="<?= e(route_url('create-service.php')) ?>">Publicar servicio</a>
    <a class="button secondary" href="<?= e(route_url('browse-requests.php')) ?>">Ver requerimientos</a>
  </div>
</section>

<section class="stats compact-stats">
  <article>
    <strong><?= e((string) $stats['services']) ?></strong>
    <p class="muted">Servicios</p>
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

<?php if ($hasServices): ?>
<section class="card">
  <h2>Mis servicios</h2>
    <div class="list">
      <?php foreach ($services as $service): ?>
        <article class="item">
          <strong><?= e($service['title']) ?></strong>
          <p class="muted"><?= e($service['category']) ?> | Desde <?= e((string) $service['price_from']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($hasDirectLeads || $hasOpenRequests): ?>
<section class="grid two">
  <?php if ($hasDirectLeads): ?>
  <section class="card">
    <h2>Solicitudes directas</h2>
      <div class="list">
        <?php foreach ($directLeads as $lead): ?>
          <article class="item">
            <h4><?= e($lead['subject']) ?></h4>
            <p class="muted"><?= e($lead['client_name']) ?> | <?= e($lead['status']) ?></p>
            <p><?= e($lead['message']) ?></p>
            <div class="meta">
              <?php if ($lead['budget'] !== null): ?>
                <span class="chip">Presupuesto <?= e((string) $lead['budget']) ?></span>
              <?php endif; ?>
              <?php if ($lead['quoted_amount'] !== null): ?>
                <span class="chip">Cotizado <?= e((string) $lead['quoted_amount']) ?></span>
              <?php endif; ?>
            </div>
            <div class="toolbar">
              <a class="button secondary" href="<?= e(route_url('direct-request-detail.php?id=' . (string) $lead['id'])) ?>">Abrir</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
  </section>
  <?php endif; ?>

  <?php if ($hasOpenRequests): ?>
  <section class="card">
    <h2>Requerimientos abiertos</h2>
      <div class="list">
        <?php foreach ($openRequests as $openRequest): ?>
          <article class="item">
            <h4><?= e($openRequest['title']) ?></h4>
            <p class="muted"><?= e($openRequest['category']) ?> | <?= e($openRequest['urgency']) ?></p>
            <div class="toolbar">
              <a class="button secondary" href="<?= e(route_url('request-detail.php?id=' . (string) $openRequest['id'])) ?>">Ver y cotizar</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
  </section>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($hasSentQuotes): ?>
<section class="card">
  <h2>Cotizaciones enviadas</h2>
    <div class="list">
      <?php foreach ($sentQuotes as $quote): ?>
        <article class="item">
          <h4><?= e($quote['title']) ?></h4>
          <p class="muted"><?= e($quote['client_name']) ?> | <?= e($quote['status']) ?></p>
          <div class="meta">
            <span class="chip">Monto <?= e((string) $quote['amount']) ?></span>
            <span class="chip"><?= e($quote['created_at']) ?></span>
          </div>
          <div class="toolbar">
            <a class="button secondary" href="<?= e(route_url('request-detail.php?id=' . (string) $quote['request_id'])) ?>">Abrir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php render_footer(); ?>
