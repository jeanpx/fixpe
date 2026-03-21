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
$opportunities = [];

foreach (array_slice($directLeads, 0, 1) as $lead) {
    $opportunities[] = [
        'title' => (string) $lead['subject'],
        'meta' => trim((string) $lead['client_name']) . ' | directa',
        'chip' => $lead['budget'] !== null ? 'S/ ' . (string) $lead['budget'] : null,
        'button' => 'Abrir',
        'href' => route_url('direct-request-detail.php?id=' . (string) $lead['id']),
    ];
}

foreach (array_slice($openRequests, 0, 1) as $openRequest) {
    $opportunities[] = [
        'title' => (string) $openRequest['title'],
        'meta' => (string) $openRequest['category'] . ' | ' . (string) $openRequest['urgency'],
        'chip' => null,
        'button' => 'Cotizar',
        'href' => route_url('request-detail.php?id=' . (string) $openRequest['id']),
    ];
}

render_header('Panel proveedor', 'body-provider-overview', 'provider-dashboard-main');
?>
<section class="card dashboard-hero">
  <p class="eyebrow">Proveedor</p>
  <h1>Hola, <?= e($displayName) ?></h1>
  <p class="muted">Plan activo: <?= e($plan) ?></p>
  <div class="provider-summary">
    <span>Servicios: <?= e((string) $stats['services']) ?></span>
    <span>Cotizaciones: <?= e((string) $stats['quotes']) ?></span>
    <span>Directas: <?= e((string) $stats['direct_requests']) ?></span>
  </div>
  <div class="toolbar">
    <a class="button" href="<?= e(route_url('create-service.php')) ?>">Publicar servicio</a>
    <a class="button secondary" href="<?= e(route_url('browse-requests.php')) ?>">Ver requerimientos</a>
  </div>
</section>

<section class="card provider-opportunities-card">
  <h2>Oportunidades</h2>
  <?php if (!empty($opportunities)): ?>
    <div class="provider-opportunities-list">
      <?php foreach ($opportunities as $opportunity): ?>
        <article class="item compact-item provider-opportunity-item">
          <div>
            <h4><?= e($opportunity['title']) ?></h4>
            <p class="muted"><?= e($opportunity['meta']) ?></p>
          </div>
          <div class="provider-opportunity-actions">
            <?php if ($opportunity['chip'] !== null): ?>
              <span class="chip"><?= e($opportunity['chip']) ?></span>
            <?php endif; ?>
            <a class="button secondary" href="<?= e($opportunity['href']) ?>"><?= e($opportunity['button']) ?></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">Todavía no hay oportunidades.</p>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
