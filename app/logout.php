<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

unset($_SESSION['user']);
set_flash('success', 'Sesion cerrada.');
redirect('login.php');
