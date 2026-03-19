<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $config;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function app_name(): string
{
    global $config;
    return $config['app_name'] ?? 'Fixpe App';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(?string $type = null): ?array
{
    if ($type === null) {
        $message = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $message;
    }

    return ($_SESSION['flash']['type'] ?? null) === $type ? $_SESSION['flash'] : null;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('dashboard.php');
    }
}

function require_auth(?string $role = null): array
{
    $user = current_user();

    if (!$user) {
        set_flash('error', 'Inicia sesion para continuar.');
        redirect('login.php');
    }

    if ($role !== null && $user['role'] !== $role) {
        set_flash('error', 'No tienes acceso a esta seccion.');
        redirect('dashboard.php');
    }

    return $user;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function plan_name_for_user(int $userId): ?string
{
    $stmt = db()->prepare(
        'SELECT p.name
         FROM subscriptions s
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE s.user_id = :user_id AND s.status = "active"
         ORDER BY s.id DESC
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    return $row['name'] ?? null;
}

function fetch_rows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ?: null;
}

function admin_exists(): bool
{
    $stmt = db()->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    return (bool) $stmt->fetch();
}

function count_rows(string $sql, array $params = []): int
{
    $row = fetch_one($sql, $params);
    if (!$row) {
        return 0;
    }

    return (int) array_values($row)[0];
}
