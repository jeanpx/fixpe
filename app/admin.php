<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('admin');
$stats = [
    'users' => fetch_rows('SELECT COUNT(*) AS total FROM users')[0]['total'] ?? 0,
    'requests' => fetch_rows('SELECT COUNT(*) AS total FROM client_requests')[0]['total'] ?? 0,
    'services' => fetch_rows('SELECT COUNT(*) AS total FROM services')[0]['total'] ?? 0,
    'quotes' => fetch_rows('SELECT COUNT(*) AS total FROM quotes')[0]['total'] ?? 0,
    'direct_requests' => fetch_rows('SELECT COUNT(*) AS total FROM direct_requests')[0]['total'] ?? 0,
];
$recentUsers = fetch_rows('SELECT full_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 10');

render_header('Panel admin');
?>
<section class="card">
  <h1>Panel admin</h1>
  <p class="muted">Hola, <?= e($user['full_name']) ?>. Desde aqui controlas usuarios, leads y membresias.</p>
</section>
<section class="stats">
  <article><strong><?= e((string) $stats['users']) ?></strong><p class="muted">Usuarios registrados</p></article>
  <article><strong><?= e((string) $stats['requests']) ?></strong><p class="muted">Solicitudes publicadas</p></article>
  <article><strong><?= e((string) $stats['services']) ?></strong><p class="muted">Servicios publicados</p></article>
</section>
<section class="stats">
  <article><strong><?= e((string) $stats['quotes']) ?></strong><p class="muted">Cotizaciones creadas</p></article>
  <article><strong><?= e((string) $stats['direct_requests']) ?></strong><p class="muted">Solicitudes directas</p></article>
  <article><strong>Marketplace</strong><p class="muted">Clientes exploran perfiles y partners cotizan requerimientos.</p></article>
</section>
<section class="table-wrap">
  <h2>Ultimos usuarios</h2>
  <table>
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Rol</th>
        <th>Registro</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentUsers as $row): ?>
        <tr>
          <td><?= e($row['full_name']) ?></td>
          <td><?= e($row['email']) ?></td>
          <td><?= e($row['role']) ?></td>
          <td><?= e($row['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php render_footer(); ?>
