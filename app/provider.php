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
$profile = fetch_rows(
    'SELECT headline, verified, availability_status
     FROM provider_profiles
     WHERE user_id = :user_id
     LIMIT 1',
    ['user_id' => $user['id']]
);
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
$firstName = trim((string) strtok((string) ($user['full_name'] ?? ''), ' '));
$displayName = $firstName !== '' ? $firstName : 'proveedor';
$hasDirectLeads = !empty($directLeads);
$hasOpenRequests = !empty($openRequests);

render_header('Panel proveedor', 'body-provider-overview', 'provider-dashboard-main');
?>
<section class="card dashboard-hero">
  <p class="eyebrow">Proveedor</p>
  <h1>Hola, <?= e($displayName) ?></h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
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

<?php if ($hasDirectLeads || $hasOpenRequests): ?>
<section class="grid two provider-overview-grid">
  <?php if ($hasDirectLeads): ?>
  <section class="card provider-column-card">
    <h2>Directas</h2>
    <div class="list compact-list">
      <?php foreach (array_slice($directLeads, 0, 2) as $lead): ?>
        <article class="item compact-item">
          <h4><?= e($lead['subject']) ?></h4>
          <p class="muted"><?= e($lead['client_name']) ?> | <?= e($lead['status']) ?></p>
          <?php if ($lead['budget'] !== null): ?>
            <div class="meta">
              <span class="chip">S/ <?= e((string) $lead['budget']) ?></span>
            </div>
          <?php endif; ?>
          <div class="toolbar">
            <a class="button secondary" href="<?= e(route_url('direct-request-detail.php?id=' . (string) $lead['id'])) ?>">Abrir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($hasOpenRequests): ?>
  <section class="card provider-column-card">
    <h2>Abiertos</h2>
    <div class="list compact-list">
      <?php foreach (array_slice($openRequests, 0, 2) as $openRequest): ?>
        <article class="item compact-item">
          <h4><?= e($openRequest['title']) ?></h4>
          <p class="muted"><?= e($openRequest['category']) ?> | <?= e($openRequest['urgency']) ?></p>
          <div class="toolbar">
            <a class="button secondary" href="<?= e(route_url('request-detail.php?id=' . (string) $openRequest['id'])) ?>">Cotizar</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php render_footer(); ?>
