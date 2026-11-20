<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';

Security::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::verifyCsrfToken()) {
    http_response_code(403);
    die('Вихід із системи дозволено лише методом POST з дійсним CSRF-токеном.');
}

Security::logoutUser();
header('Location: index.php');
exit;
