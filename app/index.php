<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = current_user();

if ($user) {
    redirect('dashboard.php');
}

render_header('Inicio');
?>
<section class="hero auth-card entry-hero">
  <span class="eyebrow">Fixpe App</span>
  <h1>Elige cómo quieres entrar</h1>
  <p class="muted">Crea tu cuenta o ingresa para continuar.</p>
  <div class="hero-actions auth-entry-actions">
    <a class="button" href="<?= e(route_url('register.php?role=client')) ?>">Soy cliente</a>
    <a class="button secondary" href="<?= e(route_url('register.php?role=provider')) ?>">Soy proveedor</a>
    <a class="button secondary" href="<?= e(route_url('login.php')) ?>">Ingresar</a>
  </div>
</section>

<section class="grid three">
  <section class="card">
    <h2>Cliente</h2>
    <p class="muted">Publica solicitudes y recibe propuestas.</p>
  </section>
  <section class="card">
    <h2>Proveedor</h2>
    <p class="muted">Muestra tus servicios y responde oportunidades.</p>
  </section>
  <section class="card">
    <h2>Admin</h2>
    <p class="muted">Gestiona usuarios, planes y actividad.</p>
  </section>
</section>
<?php render_footer(); ?>
