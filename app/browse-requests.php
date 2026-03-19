<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$provider = require_auth('provider');
$category = trim($_GET['category'] ?? '');
$urgency = trim($_GET['urgency'] ?? '');

$sql = 'SELECT
            cr.id,
            cr.title,
            cr.description,
            cr.category,
            cr.budget_min,
            cr.budget_max,
            cr.country,
            cr.urgency,
            cr.status,
            cr.created_at,
            u.full_name AS client_name,
            (
              SELECT COUNT(*)
              FROM quotes q
              WHERE q.request_id = cr.id
            ) AS quotes_count,
            (
              SELECT q.status
              FROM quotes q
              WHERE q.request_id = cr.id AND q.provider_user_id = :provider_user_id
              ORDER BY q.id DESC
              LIMIT 1
            ) AS my_quote_status
        FROM client_requests cr
        INNER JOIN users u ON u.id = cr.client_user_id
        WHERE cr.status IN ("open", "in_review", "matched")';

$params = ['provider_user_id' => $provider['id']];

if ($category !== '') {
    $sql .= ' AND cr.category LIKE :category';
    $params['category'] = '%' . $category . '%';
}

if ($urgency !== '' && in_array($urgency, ['low', 'medium', 'high'], true)) {
    $sql .= ' AND cr.urgency = :urgency';
    $params['urgency'] = $urgency;
}

$sql .= ' ORDER BY cr.created_at DESC';
$requests = fetch_rows($sql, $params);

render_header('Explorar requerimientos');
?>
<section class="card">
  <h1>Requerimientos de clientes</h1>
  <p class="muted">Revisa publicaciones abiertas y envia tu cotizacion desde la plataforma.</p>
  <form method="get" class="grid two">
    <label>
      Categoria
      <input type="text" name="category" value="<?= e($category) ?>" placeholder="Implementacion, soporte, inventario">
    </label>
    <label>
      Urgencia
      <select name="urgency">
        <option value="">Todas</option>
        <option value="low" <?= $urgency === 'low' ? 'selected' : '' ?>>Baja</option>
        <option value="medium" <?= $urgency === 'medium' ? 'selected' : '' ?>>Media</option>
        <option value="high" <?= $urgency === 'high' ? 'selected' : '' ?>>Alta</option>
      </select>
    </label>
    <div class="toolbar">
      <button type="submit">Filtrar</button>
      <a class="button secondary" href="browse-requests.php">Limpiar</a>
      <a class="button secondary" href="provider.php">Volver al panel</a>
    </div>
  </form>
</section>
<section class="stack">
  <?php if (!$requests): ?>
    <section class="card">
      <p class="muted">No hay requerimientos publicados con esos filtros.</p>
    </section>
  <?php else: ?>
    <?php foreach ($requests as $request): ?>
      <article class="item">
        <h3><?= e($request['title']) ?></h3>
        <p class="muted">Cliente: <?= e($request['client_name']) ?></p>
        <p><?= e($request['description']) ?></p>
        <div class="meta">
          <span class="chip"><?= e($request['category']) ?></span>
          <span class="chip"><?= e($request['urgency']) ?></span>
          <span class="chip"><?= e($request['country'] ?: 'Pais no definido') ?></span>
          <span class="chip">Cotizaciones: <?= e((string) $request['quotes_count']) ?></span>
          <?php if ($request['budget_min'] !== null || $request['budget_max'] !== null): ?>
            <span class="chip">
              Presupuesto <?= e((string) ($request['budget_min'] ?? '0')) ?> - <?= e((string) ($request['budget_max'] ?? '0')) ?>
            </span>
          <?php endif; ?>
          <?php if ($request['my_quote_status']): ?>
            <span class="chip">Tu cotizacion: <?= e($request['my_quote_status']) ?></span>
          <?php endif; ?>
        </div>
        <div class="toolbar">
          <a class="button" href="request-detail.php?id=<?= e((string) $request['id']) ?>">Ver y cotizar</a>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
