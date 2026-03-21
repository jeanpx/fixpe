<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

require_guest();

$requestedRole = $_GET['role'] ?? '';
$prefilledRole = in_array($requestedRole, ['client', 'provider'], true) ? $requestedRole : '';
$formData = [
    'role' => $prefilledRole,
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'country' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $formData = [
        'role' => $role,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'country' => $country,
    ];

    if (!in_array($role, ['client', 'provider'], true)) {
        set_flash('error', 'Selecciona un tipo de cuenta valido.');
    } elseif ($fullName === '' || $email === '' || $password === '') {
        set_flash('error', 'Completa nombre, correo y contrasena.');
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            set_flash('error', 'Ese correo ya esta registrado.');
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

            set_flash('success', 'Cuenta creada. Ya puedes iniciar sesion.');
            redirect('login.php');
        }
    }

    redirect('register.php' . ($role !== '' ? '?role=' . rawurlencode($role) : ''));
}

render_header('Crear cuenta');
?>
<section class="card auth-card auth-card-register">
  <div class="auth-intro">
    <span class="eyebrow">Registro rápido</span>
    <h1>Crea tu cuenta</h1>
    <p class="muted">Elige tu tipo de cuenta y completa lo básico para empezar.</p>
  </div>
  <form method="post">
    <div class="role-picker" aria-label="Tipo de cuenta">
      <label class="role-option">
        <input type="radio" name="role" value="client" <?= ($formData['role'] === 'client' || $formData['role'] === '') ? 'checked' : '' ?> required>
        <span>
          <strong>Cliente</strong>
          <small>Publica requerimientos y recibe propuestas.</small>
        </span>
      </label>
      <label class="role-option">
        <input type="radio" name="role" value="provider" <?= $formData['role'] === 'provider' ? 'checked' : '' ?> required>
        <span>
          <strong>Proveedor</strong>
          <small>Muestra tu perfil y cotiza oportunidades.</small>
        </span>
      </label>
    </div>
    <div class="grid two compact-grid">
      <label>
        Nombre completo
        <input type="text" name="full_name" value="<?= e($formData['full_name']) ?>" required>
      </label>
      <label>
        Correo
        <input type="email" name="email" value="<?= e($formData['email']) ?>" required>
      </label>
    </div>
    <div class="grid two compact-grid">
      <label>
        Teléfono
        <input type="text" name="phone" value="<?= e($formData['phone']) ?>">
      </label>
      <label>
        País
        <input type="text" name="country" value="<?= e($formData['country']) ?>">
      </label>
    </div>
    <div class="grid compact-grid">
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
