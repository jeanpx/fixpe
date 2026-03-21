<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('client');
$categoryOptions = [
    'Implementación Odoo',
    'Soporte técnico',
    'Desarrollo web',
    'Sistema a medida',
    'Automatización',
    'Integración',
    'Otro',
];
$countryOptions = ['Perú', 'Chile', 'Colombia', 'México', 'Argentina', 'Ecuador', 'Bolivia', 'España', 'Otro'];
$urgencyOptions = [
    'low' => 'Baja',
    'medium' => 'Media',
    'high' => 'Alta',
];
$formData = [
    'title' => '',
    'category' => 'Implementación Odoo',
    'description' => '',
    'budget_min' => '',
    'budget_max' => '',
    'country' => 'Perú',
    'urgency' => 'medium',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budgetMin = trim($_POST['budget_min'] ?? '');
    $budgetMax = trim($_POST['budget_max'] ?? '');
    $country = trim($_POST['country'] ?? 'Perú');
    $urgency = $_POST['urgency'] ?? 'medium';

    $formData = [
        'title' => $title,
        'category' => $category,
        'description' => $description,
        'budget_min' => $budgetMin,
        'budget_max' => $budgetMax,
        'country' => $country,
        'urgency' => $urgency,
    ];

    if ($title === '' || $category === '' || $description === '') {
        set_flash('error', 'Completa título, categoría y descripción.');
        redirect('create-request.php');
    }

    $stmt = db()->prepare(
        'INSERT INTO client_requests
        (client_user_id, title, category, description, budget_min, budget_max, country, urgency)
        VALUES (:client_user_id, :title, :category, :description, :budget_min, :budget_max, :country, :urgency)'
    );
    $stmt->execute([
        'client_user_id' => $user['id'],
        'title' => $title,
        'category' => $category,
        'description' => $description,
        'budget_min' => $budgetMin !== '' ? $budgetMin : null,
        'budget_max' => $budgetMax !== '' ? $budgetMax : null,
        'country' => $country !== '' ? $country : null,
        'urgency' => in_array($urgency, ['low', 'medium', 'high'], true) ? $urgency : 'medium',
    ]);

    set_flash('success', 'Solicitud publicada.');
    redirect('client.php');
}

render_header('Nueva solicitud');
?>
<section class="card auth-card auth-card-register request-form-card">
  <div class="auth-intro auth-intro-simple">
    <h1>Nueva solicitud</h1>
  </div>

  <form method="post">
    <label>
      Qué necesitas
      <input type="text" name="title" value="<?= e($formData['title']) ?>" placeholder="Ej. Implementación Odoo para inventario" required>
    </label>

    <div class="grid two compact-grid">
      <label>
        Categoría
        <select name="category" required>
          <?php foreach ($categoryOptions as $categoryOption): ?>
            <option value="<?= e($categoryOption) ?>" <?= $formData['category'] === $categoryOption ? 'selected' : '' ?>><?= e($categoryOption) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        Urgencia
        <select name="urgency">
          <?php foreach ($urgencyOptions as $urgencyValue => $urgencyLabel): ?>
            <option value="<?= e($urgencyValue) ?>" <?= $formData['urgency'] === $urgencyValue ? 'selected' : '' ?>><?= e($urgencyLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="grid two compact-grid">
      <label>
        Desde
        <input type="number" step="0.01" name="budget_min" value="<?= e($formData['budget_min']) ?>" placeholder="Opcional">
      </label>
      <label>
        Hasta
        <input type="number" step="0.01" name="budget_max" value="<?= e($formData['budget_max']) ?>" placeholder="Opcional">
      </label>
    </div>

    <label>
      País
      <select name="country">
        <?php foreach ($countryOptions as $countryOption): ?>
          <option value="<?= e($countryOption) ?>" <?= $formData['country'] === $countryOption ? 'selected' : '' ?>><?= e($countryOption) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Descripción
      <textarea name="description" rows="5" placeholder="Describe el alcance, tiempos o cualquier detalle importante" required><?= e($formData['description']) ?></textarea>
    </label>

    <div class="form-actions">
      <button type="submit">Publicar</button>
      <a class="button secondary" href="<?= e(route_url('client.php')) ?>">Cancelar</a>
    </div>
  </form>
</section>
<?php render_footer(); ?>
