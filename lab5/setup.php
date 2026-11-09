<?php
declare(strict_types=1);

$host = 'localhost';
$port = 3306;
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `practicum5` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `practicum5`;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `recipes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `ingredients` TEXT NOT NULL,
            `cook_time_min` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Check if table is empty, seed initial data
        $count = (int)$pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("INSERT INTO `recipes` (`title`, `ingredients`, `cook_time_min`) VALUES
            ('Український червоний борщ', 'Яловичина, буряк, капуста, картопля, морква, цибуля, томатна паста', 90),
            ('Класичні деруни зі сметаною', 'Картопля, цибуля, яйце, борошно, сметана', 25),
            ('Швидкі тости з авокадо', 'Хліб, авокадо, яйце, лимон, перець', 15),
            ('Паста Карбонара', 'Спагеті, бекон, жовтки, сир пармезан, перець', 20),
            ('Ніжні сирники', 'Сир, яйце, борошно, цукор, ваніль', 30)");
        }

        $message = "Базу даних 'practicum5' та таблицю 'recipes' успішно ініціалізовано!";
    } catch (PDOException $e) {
        $error = "Помилка підключення або ініціалізації: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ініціалізація БД — Практична №5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 600px; margin-top: 50px;">
        <header class="header">
            <span class="header-badge">Практична №5 · AJAX</span>
            <h1>Ініціалізація бази даних</h1>
            <p class="subtitle">Створення бази даних MySQL <code>practicum5</code> та тестових записів</p>
        </header>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
                <p style="margin-top: 10px;"><a href="index.html" class="btn btn-primary" style="display: inline-block; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">Перейти до застосунку AJAX →</a></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-card">
            <p>Натисніть кнопку нижче, щоб автоматично створити базу даних <code>practicum5</code> та заповнити її початковими рецептами для тестування AJAX-запитів.</p>
            <button type="submit" class="btn btn-primary" style="margin-top: 15px; width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Ініціалізувати базу даних</button>
        </form>

        <p style="text-align: center; margin-top: 20px;">
            <a href="index.html">← Повернутися до головної сторінки</a>
        </p>
    </div>
</body>
</html>
