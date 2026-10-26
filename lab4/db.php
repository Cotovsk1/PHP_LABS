<?php
declare(strict_types=1);

/**
 * Практична робота №4 — Підключення до бази даних через PDO (MySQL)
 * Варіант №6: Кулінарна книга
 */

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum4';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Якщо скрипт викликано безпосередньо або для надання зрозумілого повідомлення користувачеві
    $errorMessage = $e->getMessage();
    
    // Спробуємо перевірити, чи запущений MySQL, але ще не створено БД:
    if (str_contains($errorMessage, 'Unknown database')) {
        header('Location: setup.php?action=create_db_notice');
        exit;
    }

    // Якщо це виклик з вебінтерфейсу, виводимо зрозумілу сторінку помилки з порадами
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <title>Помилка підключення до БД — Лабораторна №4</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="page-container" style="max-width: 700px; margin-top: 40px;">
            <div class="card error-card">
                <h2>⚠️ Не вдалося підключитися до MySQL</h2>
                <p><strong>Повідомлення PDO:</strong> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <hr style="margin: 16px 0; border: 0; border-top: 1px solid #fed7aa;">
                <h3>Можливі причини та розв'язання:</h3>
                <ul style="margin: 12px 0 16px 20px; line-height: 1.6;">
                    <li>Переконайтеся, що сервер MySQL / MariaDB запущено (у XAMPP, OpenServer або Docker на порті <code>3306</code>).</li>
                    <li>Переконайтеся, що створено базу даних <code>practicum4</code>. Для автоматичного налаштування скористайтеся <a href="setup.php" class="btn btn-sm btn-primary">Майстром налаштування setup.php</a>.</li>
                    <li>Якщо встановлено інший пароль для <code>root</code>, змініть його у файлі <code>db.php</code>.</li>
                </ul>
                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <a href="setup.php" class="btn btn-primary">Запустити ініціалізацію БД (setup.php)</a>
                    <a href="index.php" class="btn btn-secondary">Спробувати знову</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

return $pdo;
