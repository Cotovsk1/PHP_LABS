<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';
require_once __DIR__ . '/lib/Cache.php';

Security::initSession();
$cache = new FileCache('cookbook_lab8_');

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum8';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken()) {
        http_response_code(403);
        $message = 'Помилка безпеки: недійсний CSRF-токен (403).';
        $messageType = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $sqlPath = __DIR__ . '/schema.sql';
            $pdo->exec((string) file_get_contents($sqlPath));

            // Генеруємо актуальний bcrypt/argon2 хеш для демо-користувача chef
            $demoHash = password_hash('Password123!', PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE `{$dbname}`.`users` SET password_hash = :hash WHERE username = 'chef'");
            $upd->execute([':hash' => $demoHash]);

            $cache->clearAll();
            $message = "Базу даних '{$dbname}', таблиці 'users' (chef / Password123!) та 'recipes' успішно створено!";
            $messageType = 'success';
        } catch (Throwable $e) {
            error_log('[SecurityAudit] Setup error: ' . $e->getMessage());
            $message = 'Не вдалося виконати ініціалізацію БД. Перевірте, чи запущений сервер MySQL/MariaDB.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ініціалізація БД — Практична №8</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 720px;">
        <header class="header">
            <div class="header-content">
                <span class="header-icon">🛡️</span>
                <div>
                    <span class="header-badge">Практична №8 · Налаштування</span>
                    <h1>Ініціалізація захищеної БД <code>practicum8</code></h1>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= Security::e($messageType) ?>"><?= Security::e($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <p style="margin-bottom: 16px; color: var(--gray-700);">
                Натисніть кнопку нижче, щоб створити базу <code>practicum8</code>, таблицю <code>users</code> (з демо-користувачем <code>chef</code> / <code>Password123!</code>) та таблицю <code>recipes</code> (з полями <code>ingredients</code>, <code>steps</code> та індексом <code>idx_recipes_cook_time</code>).
            </p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <form method="POST">
                    <?= Security::csrfInput() ?>
                    <button type="submit" class="btn btn-primary">🔄 Створити / Переініціалізувати БД</button>
                </form>
                <a href="index.php" class="btn btn-secondary">← Перейти до застосунку</a>
            </div>
        </section>
    </div>
</body>
</html>
