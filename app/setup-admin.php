<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

if (admin_exists()) {
    set_flash('error', 'Ya existe una cuenta admin. Esta pantalla ya no se puede usar.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        set_flash('error', 'Completa nombre, correo y contrasena.');
        redirect('setup-admin.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        set_flash('error', 'Ese correo ya esta registrado.');
        redirect('setup-admin.php');
    }

    $insert = db()->prepare(
        'INSERT INTO users (role, full_name, email, password_hash, phone, country)
         VALUES ("admin", :full_name, :email, :password_hash, :phone, :country)'
    );
    $insert->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'phone' => $phone !== '' ? $phone : null,
        'country' => $country !== '' ? $country : null,
    ]);

    set_flash('success', 'Admin creado. Ya puedes iniciar sesion.');
    redirect('login.php');
}

render_header('Crear admin');
?>
<section class="card">
  <h1>Crear primer admin</h1>
  <p class="muted">
    Esta pantalla solo funciona mientras no exista ningun administrador en la base de datos.
  </p>
  <form method="post">
    <div class="grid two">
      <label>
        Nombre completo
        <input type="text" name="full_name" required>
      </label>
      <label>
        Correo
        <input type="email" name="email" required>
      </label>
    </div>
    <div class="grid two">
      <label>
        Telefono
        <input type="text" name="phone">
      </label>
      <label>
        Contrasena
        <input type="password" name="password" required>
      </label>
    </div>
    <label>
      Pais
      <input type="text" name="country">
    </label>
    <div class="form-actions">
      <button type="submit">Crear admin</button>
    </div>
  </form>
</section>
<?php render_footer(); ?>
