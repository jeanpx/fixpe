<?php

declare(strict_types=1);

require_once __DIR__ . '/payment_helpers.php';

$client = require_auth('client');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ], 405);
}

if (!culqi_is_enabled()) {
    json_response([
        'ok' => false,
        'message' => 'Culqi no esta configurado.',
    ], 400);
}

$payload = request_json();
$targetType = (string) ($payload['target_type'] ?? '');
$targetId = (int) ($payload['target_id'] ?? 0);
$sourceId = trim((string) ($payload['source_id'] ?? ''));
$paymentMethod = payment_method_from_source_id($sourceId, (string) ($payload['payment_method'] ?? 'tarjeta'));

if ($targetType === '' || $targetId <= 0 || $sourceId === '') {
    json_response([
        'ok' => false,
        'message' => 'Datos de pago incompletos.',
    ], 422);
}

$target = payment_target_for_client((int) $client['id'], $targetType, $targetId);

if (!$target) {
    json_response([
        'ok' => false,
        'message' => 'No se encontro el pago solicitado.',
    ], 404);
}

if (!payment_target_is_payable($target)) {
    json_response([
        'ok' => false,
        'message' => 'Este pago ya no esta disponible.',
    ], 409);
}

try {
    $charge = culqi_charge(
        $sourceId,
        (int) round($target['amount'] * 100),
        (string) $client['email'],
        $target['summary'],
        [
            'target_type' => $target['type'],
            'target_id' => (string) $target['id'],
            'client_user_id' => (string) $target['client_user_id'],
            'provider_user_id' => (string) $target['provider_user_id'],
        ]
    );

    payment_complete($target, $paymentMethod, $sourceId, $charge);

    json_response([
        'ok' => true,
        'message' => 'Pago aprobado. La operacion fue actualizada.',
        'redirect' => route_url(payment_target_redirect($target)),
    ]);
} catch (Throwable $exception) {
    payment_register_failure($target, $paymentMethod, $sourceId, $exception->getMessage());

    json_response([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
