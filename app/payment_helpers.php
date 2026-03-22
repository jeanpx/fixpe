<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function payment_method_from_source_id(string $sourceId, ?string $fallback = null): string
{
    $normalizedFallback = strtolower(trim((string) $fallback));

    if (str_starts_with($sourceId, 'ype_')) {
        return 'yape';
    }

    if (in_array($normalizedFallback, ['tarjeta', 'yape'], true)) {
        return $normalizedFallback;
    }

    return 'tarjeta';
}

function payment_target_for_client(int $clientUserId, string $targetType, int $targetId): ?array
{
    if ($targetType === 'quote') {
        $quote = fetch_one(
            'SELECT
                q.id,
                q.request_id,
                q.client_user_id,
                q.provider_user_id,
                q.amount,
                q.status,
                q.delivery_days,
                q.message,
                cr.title,
                cr.status AS request_status,
                u.full_name AS provider_name,
                p.id AS payment_id,
                p.status AS payment_status
             FROM quotes q
             INNER JOIN client_requests cr ON cr.id = q.request_id
             INNER JOIN users u ON u.id = q.provider_user_id
             LEFT JOIN payments p ON p.payment_target_type = "quote" AND p.payment_target_id = q.id
             WHERE q.id = :id AND q.client_user_id = :client_user_id
             LIMIT 1',
            [
                'id' => $targetId,
                'client_user_id' => $clientUserId,
            ]
        );

        if (!$quote) {
            return null;
        }

        return [
            'type' => 'quote',
            'id' => (int) $quote['id'],
            'request_id' => (int) $quote['request_id'],
            'client_user_id' => (int) $quote['client_user_id'],
            'provider_user_id' => (int) $quote['provider_user_id'],
            'provider_name' => (string) $quote['provider_name'],
            'title' => (string) $quote['title'],
            'description' => (string) $quote['message'],
            'amount' => (float) $quote['amount'],
            'status' => (string) $quote['status'],
            'payment_status' => $quote['payment_status'] !== null ? (string) $quote['payment_status'] : null,
            'summary' => 'Cotizacion para "' . (string) $quote['title'] . '"',
        ];
    }

    if ($targetType === 'direct_request') {
        $directRequest = fetch_one(
            'SELECT
                dr.id,
                dr.client_user_id,
                dr.provider_user_id,
                dr.subject,
                dr.provider_response,
                dr.quoted_amount,
                dr.status,
                u.full_name AS provider_name,
                p.id AS payment_id,
                p.status AS payment_status
             FROM direct_requests dr
             INNER JOIN users u ON u.id = dr.provider_user_id
             LEFT JOIN payments p ON p.payment_target_type = "direct_request" AND p.payment_target_id = dr.id
             WHERE dr.id = :id AND dr.client_user_id = :client_user_id
             LIMIT 1',
            [
                'id' => $targetId,
                'client_user_id' => $clientUserId,
            ]
        );

        if (!$directRequest) {
            return null;
        }

        return [
            'type' => 'direct_request',
            'id' => (int) $directRequest['id'],
            'request_id' => null,
            'client_user_id' => (int) $directRequest['client_user_id'],
            'provider_user_id' => (int) $directRequest['provider_user_id'],
            'provider_name' => (string) $directRequest['provider_name'],
            'title' => (string) $directRequest['subject'],
            'description' => (string) ($directRequest['provider_response'] ?? ''),
            'amount' => (float) ($directRequest['quoted_amount'] ?? 0),
            'status' => (string) $directRequest['status'],
            'payment_status' => $directRequest['payment_status'] !== null ? (string) $directRequest['payment_status'] : null,
            'summary' => 'Solicitud directa "' . (string) $directRequest['subject'] . '"',
        ];
    }

    return null;
}

function payment_target_is_payable(array $target): bool
{
    if ($target['amount'] <= 0) {
        return false;
    }

    if ($target['payment_status'] === 'paid') {
        return false;
    }

    if ($target['type'] === 'quote') {
        return in_array($target['status'], ['pending', 'accepted'], true);
    }

    return in_array($target['status'], ['reviewed', 'accepted'], true);
}

function payment_target_redirect(array $target): string
{
    if ($target['type'] === 'quote') {
        return 'request-detail.php?id=' . (string) $target['request_id'];
    }

    return 'direct-request-detail.php?id=' . (string) $target['id'];
}

function payment_register_failure(array $target, string $paymentMethod, string $sourceId, string $message): void
{
    $existing = fetch_one(
        'SELECT id FROM payments WHERE payment_target_type = :type AND payment_target_id = :target_id LIMIT 1',
        [
            'type' => $target['type'],
            'target_id' => $target['id'],
        ]
    );

    $payload = [
        'payment_target_type' => $target['type'],
        'payment_target_id' => $target['id'],
        'client_user_id' => $target['client_user_id'],
        'provider_user_id' => $target['provider_user_id'],
        'amount' => $target['amount'],
        'provider_name' => $target['provider_name'],
        'description' => $target['summary'],
        'payment_method' => $paymentMethod,
        'culqi_source_id' => $sourceId,
        'culqi_response_json' => json_encode(['message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE payments
             SET amount = :amount,
                 provider_name = :provider_name,
                 description = :description,
                 payment_method = :payment_method,
                 status = "failed",
                 culqi_source_id = :culqi_source_id,
                 culqi_response_json = :culqi_response_json,
                 paid_at = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($payload + ['id' => $existing['id']]);
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO payments
         (payment_target_type, payment_target_id, client_user_id, provider_user_id, amount, currency, provider_name, description, payment_method, status, culqi_source_id, culqi_response_json)
         VALUES (:payment_target_type, :payment_target_id, :client_user_id, :provider_user_id, :amount, "PEN", :provider_name, :description, :payment_method, "failed", :culqi_source_id, :culqi_response_json)'
    );
    $stmt->execute($payload);
}

function payment_complete(array $target, string $paymentMethod, string $sourceId, array $charge): void
{
    $existing = fetch_one(
        'SELECT id FROM payments WHERE payment_target_type = :type AND payment_target_id = :target_id LIMIT 1',
        [
            'type' => $target['type'],
            'target_id' => $target['id'],
        ]
    );

    $payload = [
        'payment_target_type' => $target['type'],
        'payment_target_id' => $target['id'],
        'client_user_id' => $target['client_user_id'],
        'provider_user_id' => $target['provider_user_id'],
        'amount' => $target['amount'],
        'provider_name' => $target['provider_name'],
        'description' => $target['summary'],
        'payment_method' => $paymentMethod,
        'culqi_charge_id' => (string) ($charge['id'] ?? ''),
        'culqi_source_id' => $sourceId,
        'culqi_response_json' => json_encode($charge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    db()->beginTransaction();

    try {
        if ($existing) {
            $stmt = db()->prepare(
                'UPDATE payments
                 SET amount = :amount,
                     provider_name = :provider_name,
                     description = :description,
                     payment_method = :payment_method,
                     status = "paid",
                     culqi_charge_id = :culqi_charge_id,
                     culqi_source_id = :culqi_source_id,
                     culqi_response_json = :culqi_response_json,
                     paid_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute($payload + ['id' => $existing['id']]);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO payments
                 (payment_target_type, payment_target_id, client_user_id, provider_user_id, amount, currency, provider_name, description, payment_method, status, culqi_charge_id, culqi_source_id, culqi_response_json, paid_at)
                 VALUES (:payment_target_type, :payment_target_id, :client_user_id, :provider_user_id, :amount, "PEN", :provider_name, :description, :payment_method, "paid", :culqi_charge_id, :culqi_source_id, :culqi_response_json, NOW())'
            );
            $stmt->execute($payload);
        }

        if ($target['type'] === 'quote') {
            $quoteStmt = db()->prepare(
                'UPDATE quotes
                 SET status = "accepted", updated_at = NOW()
                 WHERE id = :id'
            );
            $quoteStmt->execute(['id' => $target['id']]);

            $requestStmt = db()->prepare(
                'UPDATE client_requests
                 SET status = "matched", updated_at = NOW()
                 WHERE id = :request_id'
            );
            $requestStmt->execute(['request_id' => $target['request_id']]);

            $otherQuotesStmt = db()->prepare(
                'UPDATE quotes
                 SET status = "rejected", updated_at = NOW()
                 WHERE request_id = :request_id AND id <> :quote_id AND status = "pending"'
            );
            $otherQuotesStmt->execute([
                'request_id' => $target['request_id'],
                'quote_id' => $target['id'],
            ]);
        } else {
            $directStmt = db()->prepare(
                'UPDATE direct_requests
                 SET status = "accepted", updated_at = NOW()
                 WHERE id = :id'
            );
            $directStmt->execute(['id' => $target['id']]);
        }

        db()->commit();
    } catch (Throwable $exception) {
        db()->rollBack();
        throw $exception;
    }
}
