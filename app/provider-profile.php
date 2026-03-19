<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$client = require_auth('client');
$providerId = (int) ($_GET['id'] ?? 0);

$provider = fetch_one(
    'SELECT
        u.id,
        u.full_name,
        u.country,
        pp.headline,
        pp.bio,
        pp.specialties,
        pp.hourly_rate,
        pp.experience_years,
        pp.verified,
        pp.availability_status
     FROM users u
     INNER JOIN provider_profiles pp ON pp.user_id = u.id
     WHERE u.id = :id AND u.role = "provider" AND u.is_active = 1
     LIMIT 1',
    ['id' => $providerId]
);

if (!$provider) {
    set_flash('error', 'Proveedor no encontrado.');
    redirect('explore-providers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    if ($subject === '' || $message === '') {
        set_flash('error', 'Completa asunto y descripción.');
        redirect('provider-profile.php?id=' . $providerId);
    }

    $stmt = db()->prepare(
        'INSERT INTO direct_requests
        (client_user_id, provider_user_id, subject, message, budget, status)
        VALUES (:client_user_id, :provider_user_id, :subject, :message, :budget, "pending")'
    );
    $stmt->execute([
        'client_user_id' => $client['id'],
        'provider_user_id' => $providerId,
        'subject' => $subject,
        'message' => $message,
        'budget' => $budget !== '' ? $budget : null,
    ]);

    set_flash('success', 'Solicitud directa enviada al especialista.');
    redirect('provider-profile.php?id=' . $providerId);
}

$services = fetch_rows(
    'SELECT title, category, description, price_from, delivery_days
     FROM services
     WHERE provider_user_id = :provider_user_id AND is_active = 1
     ORDER BY id DESC',
    ['provider_user_id' => $providerId]
);

render_header('Perfil de especialista');
?>
<section class="card">
  <h1><?= e($provider['full_name']) ?></h1>
  <p><?= e($provider['headline']) ?></p>
  <p class="muted"><?= e($provider['bio'] ?: 'Este especialista aún no completó su biografía.') ?></p>
  <div class="meta">
    <span class="chip"><?= e($provider['country'] ?: 'País no definido') ?></span>
    <span class="chip"><?= e($provider['availability_status']) ?></span>
    <?php if ($provider['hourly_rate'] !== null): ?>
      <span class="chip">Tarifa/hora <?= e((string) $provider['hourly_rate']) ?></span>
    <?php endif; ?>
    <?php if ($provider['experience_years'] !== null): ?>
      <span class="chip"><?= e((string) $provider['experience_years']) ?> años</span>
    <?php endif; ?>
    <?php if ((int) $provider['verified'] === 1): ?>
      <span class="chip">Verificado</span>
    <?php endif; ?>
  </div>
</section>
<section class="grid two">
  <section class="card">
    <h2>Especialidades y servicios</h2>
    <p class="muted"><?= e($provider['specialties'] ?: 'Sin especialidades cargadas') ?></p>
    <?php if (!$services): ?>
      <p class="muted">Aún no tiene servicios publicados.</p>
    <?php else: ?>
      <div class="list">
        <?php foreach ($services as $service): ?>
          <article class="item">
            <h4><?= e($service['title']) ?></h4>
            <p class="muted"><?= e($service['category']) ?></p>
            <p><?= e($service['description']) ?></p>
            <div class="meta">
              <?php if ($service['price_from'] !== null): ?>
                <span class="chip">Desde <?= e((string) $service['price_from']) ?></span>
              <?php endif; ?>
              <?php if ($service['delivery_days'] !== null): ?>
                <span class="chip"><?= e((string) $service['delivery_days']) ?> días</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2>Solicitar ayuda directa</h2>
    <p class="muted">Esta solicitud llega al panel del partner para que te responda dentro de la plataforma.</p>
    <form method="post">
      <label>
        Asunto
        <input type="text" name="subject" required>
      </label>
      <label>
        Presupuesto estimado
        <input type="number" step="0.01" name="budget">
      </label>
      <label>
        Descripción
        <textarea name="message" rows="7" required></textarea>
      </label>
      <div class="toolbar">
        <button type="submit">Enviar solicitud</button>
        <a class="button secondary" href="explore-providers.php">Volver</a>
      </div>
    </form>
  </section>
</section>
<?php render_footer(); ?>
