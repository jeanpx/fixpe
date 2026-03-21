<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = current_user();

if ($user) {
    redirect('dashboard.php');
}

render_header('Inicio');
?>
<section class="hero">
  <h1>Fixpe ya puede venderse como SaaS B2B</h1>
  <p class="muted">
    La plataforma ya tiene clientes, proveedores, solicitudes, cotizaciones y membresías.
    El objetivo comercial es convertir ese flujo en ingreso recurrente con cobro mensual, visibilidad premium y fee por cierre.
  </p>
  <div class="hero-actions">
    <a class="button" href="register.php?role=client">Crear cuenta cliente</a>
    <a class="button secondary" href="register.php?role=provider">Crear cuenta proveedor</a>
    <a class="button secondary" href="login.php">Ingresar</a>
  </div>
</section>
<section class="stats">
  <article>
    <strong>Clientes</strong>
    <p class="muted">Publican necesidades, comparan propuestas y pueden subir a un plan con prioridad.</p>
  </article>
  <article>
    <strong>Proveedores</strong>
    <p class="muted">Publican servicios, reciben leads y pagan por mejor exposición y acceso.</p>
  </article>
  <article>
    <strong>Admin</strong>
    <p class="muted">Controla actividad, membresías y conversión del marketplace.</p>
  </article>
</section>
<section class="grid three">
  <section class="card">
    <h2>Suscripción</h2>
    <p class="muted">Plan mensual para empresas que necesitan publicar más solicitudes o atención prioritaria.</p>
  </section>
  <section class="card">
    <h2>Lead premium</h2>
    <p class="muted">Proveedores pagan por aparecer primero, recibir solicitudes directas y ver oportunidades antes.</p>
  </section>
  <section class="card">
    <h2>Comisión</h2>
    <p class="muted">Cada proyecto cerrado puede dejar un fee adicional sin depender de vender horas manualmente.</p>
  </section>
</section>
<?php render_footer(); ?>
