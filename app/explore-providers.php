<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('client');
$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = 'SELECT
            u.id,
            u.full_name,
            u.country,
            pp.headline,
            pp.specialties,
            pp.experience_years,
            pp.hourly_rate,
            pp.verified,
            pp.availability_status,
            COUNT(s.id) AS services_count,
            MIN(s.price_from) AS min_price
        FROM users u
        INNER JOIN provider_profiles pp ON pp.user_id = u.id
        LEFT JOIN services s ON s.provider_user_id = u.id AND s.is_active = 1
        WHERE u.role = "provider" AND u.is_active = 1';

$params = [];

if ($query !== '') {
    $sql .= ' AND (u.full_name LIKE :query OR pp.headline LIKE :query OR pp.specialties LIKE :query)';
    $params['query'] = '%' . $query . '%';
}

if ($category !== '') {
    $sql .= ' AND EXISTS (
        SELECT 1
        FROM services sx
        WHERE sx.provider_user_id = u.id AND sx.is_active = 1 AND sx.category LIKE :category
    )';
    $params['category'] = '%' . $category . '%';
}

$sql .= '
        GROUP BY
            u.id, u.full_name, u.country, pp.headline, pp.specialties,
            pp.experience_years, pp.hourly_rate, pp.verified, pp.availability_status
        ORDER BY pp.verified DESC, services_count DESC, u.full_name ASC';

$providers = fetch_rows($sql, $params);

render_header('Explorar especialistas');
?>
<section class="card">
  <h1>Explorar especialistas Odoo</h1>
  <p class="muted">
    Busca implementadores y partners. Puedes revisar su perfil y enviarles una solicitud directa.
  </p>
  <form method="get" class="grid two">
    <label>
      Buscar
      <input type="text" name="q" value="<?= e($query) ?>" placeholder="Nombre, especialidad o perfil">
    </label>
    <label>
      Categoría
      <input type="text" name="category" value="<?= e($category) ?>" placeholder="Inventario, contabilidad, soporte">
    </label>
    <div class="toolbar">
      <button type="submit">Filtrar</button>
      <a class="button secondary" href="explore-providers.php">Limpiar</a>
      <a class="button secondary" href="client.php">Volver al panel</a>
    </div>
  </form>
</section>
<section class="stack">
  <?php if (!$providers): ?>
    <section class="card">
      <p class="muted">No se encontraron especialistas con esos filtros.</p>
    </section>
  <?php else: ?>
    <?php foreach ($providers as $provider): ?>
      <article class="item">
        <h3><?= e($provider['full_name']) ?></h3>
        <p><?= e($provider['headline']) ?></p>
        <p class="muted"><?= e($provider['specialties'] ?: 'Sin especialidades cargadas') ?></p>
        <div class="meta">
          <span class="chip"><?= e($provider['country'] ?: 'País no definido') ?></span>
          <span class="chip"><?= e($provider['availability_status']) ?></span>
          <span class="chip">Servicios: <?= e((string) $provider['services_count']) ?></span>
          <?php if ($provider['min_price'] !== null): ?>
            <span class="chip">Desde <?= e((string) $provider['min_price']) ?></span>
          <?php endif; ?>
          <?php if ((int) $provider['verified'] === 1): ?>
            <span class="chip">Verificado</span>
          <?php endif; ?>
        </div>
        <div class="toolbar">
          <a class="button" href="provider-profile.php?id=<?= e((string) $provider['id']) ?>">Ver perfil</a>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
