<?php
declare(strict_types=1);

$host = 'localhost';
$port = 3306;
$username = 'root';
$password = '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `practicum6` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `practicum6`;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `recipes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `ingredients` TEXT NOT NULL,
            `cook_time_min` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Seed initial data
        $count = (int)$pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("INSERT INTO `recipes` (`title`, `ingredients`, `cook_time_min`) VALUES
            ('Український червоний борщ', 'Яловичина, буряк, капуста, картопля, морква, цибуля, томатна паста', 90),
            ('Класичні деруни зі сметаною', 'Картопля, цибуля, яйце, борошно, сметана', 25),
            ('Швидкі тости з авокадо', 'Хліб, авокадо, яйце, лимон, перець', 15),
            ('Паста Карбонара', 'Спагеті, бекон, жовтки, сир пармезан, перець', 20),
            ('Ніжні сирники', 'Сир, яйце, борошно, цукор, ваніль', 30)");
        }

        $message = "Базу даних 'practicum6' та таблицю 'recipes' успішно ініціалізовано!";
    } catch (PDOException $e) {
        $error = "Помилка ініціалізації: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Ініціалізація API — Практична №6</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 600px; margin-top: 50px;">
        <header class="header">
            <div class="header-content">
                <div>
                    <span class="header-badge">Практична №6 · API</span>
                    <h1>Налаштування API</h1>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
                <p style="margin-top:10px;"><a href="api.php?resource=recipes" class="btn btn-primary" style="text-decoration:none;">Перевірити GET API →</a></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <p>Натисніть кнопку нижче, щоб створити БД <code>practicum6</code> для тестування API.</p>
            <form method="post">
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Ініціалізувати базу даних</button>
            </form>
        </div>

        <section class="results-section" style="margin-top: 30px;">
            <h3>Приклади тестування:</h3>
            <ul style="margin-top: 10px; line-height: 1.8;">
                <li><code>GET api.php?resource=recipes</code> — список</li>
                <li><code>GET api.php?resource=recipes&id=1</code> — один запис</li>
                <li><code>POST api.php?resource=recipes&action=search</code> (параметр <code>max_time</code>) — пошук</li>
            </ul>
        </section>
    </div>
</body>
</html>