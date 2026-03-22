<?php

declare(strict_types=1);

ini_set('default_charset', 'UTF-8');

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

function app_config(string $key, mixed $default = null): mixed
{
    global $config;
    return $config[$key] ?? $default;
}

function asset_url(string $path): string
{
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    return $path . '?v=' . rawurlencode($version);
}

function route_url(string $path): string
{
    $parts = explode('?', $path, 2);
    $base = trim($parts[0]);
    $query = $parts[1] ?? '';

    if ($base === '' || $base === 'index.php') {
        $clean = './';
    } else {
        $clean = preg_replace('/\.php$/', '', $base) ?? $base;
    }

    if ($query !== '') {
        $clean .= '?' . $query;
    }

    return $clean;
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app_url', ''), '/');
    $cleanPath = ltrim($path, '/');

    if ($base === '') {
        return $cleanPath !== '' ? $cleanPath : './';
    }

    return $cleanPath !== '' ? $base . '/' . $cleanPath : $base;
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
        set_flash('error', 'Inicia sesión para continuar.');
        redirect('login.php');
    }

    if ($role !== null && $user['role'] !== $role) {
        set_flash('error', 'No tienes acceso a esta sección.');
        redirect('dashboard.php');
    }

    return $user;
}

function redirect(string $path): void
{
    header('Location: ' . route_url($path));
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

function format_amount(float $amount, string $currency = 'PEN'): string
{
    $symbol = strtoupper($currency) === 'PEN' ? 'S/ ' : strtoupper($currency) . ' ';
    return $symbol . number_format($amount, 2, '.', ',');
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    return is_array($decoded) ? $decoded : [];
}

function culqi_is_enabled(): bool
{
    return (bool) app_config('culqi_enabled', false)
        && app_config('culqi_public_key', '') !== ''
        && app_config('culqi_private_key', '') !== '';
}

function culqi_checkout_config(): array
{
    return [
        'public_key' => (string) app_config('culqi_public_key', ''),
        'rsa_id' => (string) app_config('culqi_rsa_id', ''),
        'rsa_public_key' => (string) app_config('culqi_rsa_public_key', ''),
    ];
}

function culqi_charge(string $sourceId, int $amountInCents, string $email, string $description, array $metadata = []): array
{
    $privateKey = (string) app_config('culqi_private_key', '');

    if ($privateKey === '') {
        throw new RuntimeException('CULQI_PRIVATE_KEY no configurada.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extension cURL no esta habilitada en PHP.');
    }

    $payload = [
        'amount' => $amountInCents,
        'currency_code' => 'PEN',
        'email' => $email,
        'source_id' => $sourceId,
        'capture' => true,
        'description' => $description,
        'metadata' => $metadata,
    ];

    $ch = curl_init('https://api.culqi.com/v2/charges');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $privateKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 30,
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        throw new RuntimeException('No se pudo conectar con Culqi.');
    }

    $response = json_decode($rawResponse, true);
    if (!is_array($response)) {
        throw new RuntimeException('Respuesta invalida de Culqi.');
    }

    if ($httpCode >= 400) {
        $message = $response['merchant_message'] ?? $response['user_message'] ?? $response['message'] ?? 'Pago rechazado por Culqi.';
        throw new RuntimeException((string) $message);
    }

    return $response;
}
