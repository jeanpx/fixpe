<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Credenciales inválidas.');
        redirect('login.php');
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
    ];

    redirect('dashboard.php');
}

render_header('Ingresar');
?>
<section class="card">
  <h1>Ingresar</h1>
  <form method="post">
    <label>
      Correo
      <input type="email" name="email" required>
    </label>
    <label>
      Contraseña
      <input type="password" name="password" required>
    </label>
    <div class="form-actions">
      <button type="submit">Entrar</button>
    </div>
  </form>
</section>
<?php render_footer(); ?>
