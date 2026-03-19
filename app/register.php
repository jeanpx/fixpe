<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if (!in_array($role, ['client', 'provider'], true)) {
        set_flash('error', 'Selecciona un tipo de cuenta válido.');
    } elseif ($fullName === '' || $email === '' || $password === '') {
        set_flash('error', 'Completa nombre, correo y contraseña.');
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            set_flash('error', 'Ese correo ya está registrado.');
        } else {
            $insert = db()->prepare(
                'INSERT INTO users (role, full_name, email, password_hash, phone, country)
                 VALUES (:role, :full_name, :email, :password_hash, :phone, :country)'
            );
            $insert->execute([
                'role' => $role,
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone !== '' ? $phone : null,
                'country' => $country !== '' ? $country : null,
            ]);

            $userId = (int) db()->lastInsertId();

            if ($role === 'provider') {
                $profile = db()->prepare(
                    'INSERT INTO provider_profiles (user_id, headline, bio, specialties)
                     VALUES (:user_id, :headline, :bio, :specialties)'
                );
                $profile->execute([
                    'user_id' => $userId,
                    'headline' => 'Especialista Odoo',
                    'bio' => '',
                    'specialties' => '',
                ]);
            }

            $planCode = $role === 'provider' ? 'provider_free' : 'client_free';
            $planStmt = db()->prepare('SELECT id FROM plans WHERE code = :code LIMIT 1');
            $planStmt->execute(['code' => $planCode]);
            $plan = $planStmt->fetch();

            if ($plan) {
                $sub = db()->prepare(
                    'INSERT INTO subscriptions (user_id, plan_id, status, start_date)
                     VALUES (:user_id, :plan_id, "active", NOW())'
                );
                $sub->execute([
                    'user_id' => $userId,
                    'plan_id' => $plan['id'],
                ]);
            }

            set_flash('success', 'Cuenta creada. Ya puedes iniciar sesión.');
            redirect('login.php');
        }
    }

    redirect('register.php');
}

render_header('Crear cuenta');
?>
<section class="card">
  <h1>Crear cuenta</h1>
  <form method="post">
    <div class="grid two">
      <label>
        Tipo de cuenta
        <select name="role" required>
          <option value="">Selecciona</option>
          <option value="client">Cliente</option>
          <option value="provider">Proveedor</option>
        </select>
      </label>
      <label>
        Nombre completo
        <input type="text" name="full_name" required>
      </label>
    </div>
    <div class="grid two">
      <label>
        Correo
        <input type="email" name="email" required>
      </label>
      <label>
        Teléfono
        <input type="text" name="phone">
      </label>
    </div>
    <div class="grid two">
      <label>
        País
        <input type="text" name="country">
      </label>
      <label>
        Contraseña
        <input type="password" name="password" required>
      </label>
    </div>
    <div class="form-actions">
      <button type="submit">Crear cuenta</button>
    </div>
  </form>
</section>
<?php render_footer(); ?>
