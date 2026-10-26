<?php
declare(strict_types=1);

/**
 * Практична робота №4: Майстер налаштування та ініціалізації бази даних
 * Створює базу даних practicum4 та таблицю recipes з тестовими записами
 */

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum4';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'init_db') {
    try {
        // Підключаємося без зазначення dbname для створення бази даних
        $pdoRoot = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Читаємо схему
        $sqlPath = __DIR__ . '/schema.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("Файл schema.sql не знайдено!");
        }

        $sql = file_get_contents($sqlPath);

        // Виконуємо запити
        $pdoRoot->exec($sql);

        $message = "Базу даних '{$dbname}' та таблицю 'recipes' успішно створено та заповнено початковими даними!";
        $messageType = 'success';
    } catch (Throwable $e) {
        $message = "Помилка ініціалізації: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ініціалізація БД — Лабораторна №4</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 800px; margin-top: 40px;">
        <header class="header mini-header">
            <div class="header-content">
                <span class="header-icon">⚙️</span>
                <div>
                    <span class="header-badge">Майстер налаштування</span>
                    <h1>Ініціалізація бази даних MySQL</h1>
                    <p class="subtitle">Створення бази даних <code>practicum4</code> та таблиці <code>recipes</code> для варіанта №6.</p>
                </div>
            </div>
        </header>

        <main class="card">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>📋 Параметри підключення:</h3>
                <ul>
                    <li><strong>Хост:</strong> <?= htmlspecialchars($host) ?>:<?= htmlspecialchars((string)$port) ?></li>
                    <li><strong>База даних:</strong> <?= htmlspecialchars($dbname) ?></li>
                    <li><strong>Користувач:</strong> <?= htmlspecialchars($user) ?></li>
                    <li><strong>Кодування:</strong> <?= htmlspecialchars($charset) ?></li>
                </ul>
            </div>

            <div style="margin: 24px 0;">
                <p>Натисніть кнопку нижче, щоб автоматично виконати SQL-скрипт з <code>schema.sql</code>. Це створить базу даних, таблицю та додасть 6 готових кулінарних рецептів.</p>
            </div>

            <form action="setup.php" method="POST" style="display: flex; gap: 12px; align-items: center;">
                <input type="hidden" name="action" value="init_db">
                <button type="submit" class="btn btn-primary">
                    ⚡ Створити/переініціалізувати базу даних
                </button>
                <a href="index.php" class="btn btn-secondary">
                    Перейти до застосунку (index.php)
                </a>
            </form>

            <hr style="margin: 32px 0 20px; border: 0; border-top: 1px solid var(--gray-200);">

            <h3>💡 Ручне виконання через консоль або phpMyAdmin:</h3>
            <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 6px;">
                Ви також можете імпортувати файл <code>schema.sql</code> вручну у <strong>phpMyAdmin</strong> або виконати в консолі:
            </p>
            <pre style="background: var(--gray-900); color: #38bdf8; padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.85rem; overflow-x: auto; margin-top: 8px;">mysql -u root -p &lt; schema.sql</pre>
        </main>
    </div>
</body>
</html>
