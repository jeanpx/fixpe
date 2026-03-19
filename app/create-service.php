<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$user = require_auth('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceFrom = trim($_POST['price_from'] ?? '');
    $deliveryDays = trim($_POST['delivery_days'] ?? '');

    if ($title === '' || $category === '' || $description === '') {
        set_flash('error', 'Completa título, categoría y descripción.');
        redirect('create-service.php');
    }

    $stmt = db()->prepare(
        'INSERT INTO services
        (provider_user_id, category, title, description, price_from, delivery_days)
        VALUES (:provider_user_id, :category, :title, :description, :price_from, :delivery_days)'
    );
    $stmt->execute([
        'provider_user_id' => $user['id'],
        'category' => $category,
        'title' => $title,
        'description' => $description,
        'price_from' => $priceFrom !== '' ? $priceFrom : null,
        'delivery_days' => $deliveryDays !== '' ? $deliveryDays : null,
    ]);

    set_flash('success', 'Servicio publicado.');
    redirect('provider.php');
}

render_header('Nuevo servicio');
?>
<section class="card">
  <h1>Publicar servicio</h1>
  <form method="post">
    <label>
      Título
      <input type="text" name="title" required>
    </label>
    <div class="grid two">
      <label>
        Categoría
        <input type="text" name="category" placeholder="Soporte Odoo" required>
      </label>
      <label>
        Precio desde
        <input type="number" step="0.01" name="price_from">
      </label>
    </div>
    <label>
      Días de entrega
      <input type="number" name="delivery_days">
    </label>
    <label>
      Descripción
      <textarea name="description" rows="6" required></textarea>
    </label>
    <div class="form-actions">
      <button type="submit">Guardar</button>
      <a class="button secondary" href="provider.php">Volver</a>
    </div>
  </form>
</section>
<?php render_footer(); ?>
