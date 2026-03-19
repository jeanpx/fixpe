<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budgetMin = trim($_POST['budget_min'] ?? '');
    $budgetMax = trim($_POST['budget_max'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $urgency = $_POST['urgency'] ?? 'medium';

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
<section class="card">
  <h1>Publicar solicitud</h1>
  <form method="post">
    <label>
      Título
      <input type="text" name="title" required>
    </label>
    <div class="grid two">
      <label>
        Categoría
        <input type="text" name="category" placeholder="Implementación Odoo" required>
      </label>
      <label>
        Urgencia
        <select name="urgency">
          <option value="low">Baja</option>
          <option value="medium" selected>Media</option>
          <option value="high">Alta</option>
        </select>
      </label>
    </div>
    <div class="grid two">
      <label>
        Presupuesto mínimo
        <input type="number" step="0.01" name="budget_min">
      </label>
      <label>
        Presupuesto máximo
        <input type="number" step="0.01" name="budget_max">
      </label>
    </div>
    <label>
      País
      <input type="text" name="country">
    </label>
    <label>
      Descripción
      <textarea name="description" rows="6" required></textarea>
    </label>
    <div class="form-actions">
      <button type="submit">Guardar</button>
      <a class="button secondary" href="client.php">Volver</a>
    </div>
  </form>
</section>
<?php render_footer(); ?>
