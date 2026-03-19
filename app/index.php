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
  <h1>Marketplace MVP de Fixpe</h1>
  <p class="muted">
    Esta app ya usa tu base MySQL para registrar clientes, proveedores y membresias.
    El siguiente paso es empezar a cargar usuarios reales y validar el flujo comercial.
  </p>
  <div class="hero-actions">
    <a class="button" href="register.php">Crear cuenta</a>
    <a class="button secondary" href="login.php">Ingresar</a>
  </div>
</section>
<section class="stats">
  <article>
    <strong>Clientes</strong>
    <p class="muted">Publican requerimientos y pueden tener plan Business.</p>
  </article>
  <article>
    <strong>Proveedores</strong>
    <p class="muted">Crean perfil, servicios y aplican a solicitudes.</p>
  </article>
  <article>
    <strong>Admin</strong>
    <p class="muted">Aprueba, revisa matches y controla membresias.</p>
  </article>
</section>
<?php render_footer(); ?>
