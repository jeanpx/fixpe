<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = require_auth();

if ($user['role'] === 'admin') {
    redirect('admin.php');
}

if ($user['role'] === 'provider') {
    redirect('provider.php');
}

redirect('client.php');
