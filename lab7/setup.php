<?php
declare(strict_types=1);

/**
 * Практична робота №7: Майстер налаштування БД, індексу cook_time_min та тестового датасету для ab.
 */

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum7';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

require_once __DIR__ . '/lib/Cache.php';
$cache = new FileCache('cookbook_lab7_');

$message = null;
$messageType = 'info';
$explainResult = null;
$indexExists = false;
$totalRows = 0;

function connectRoot(string $host, string $port, string $user, string $pass, string $charset): PDO
{
    return new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

try {
    $pdo = connectRoot($host, $port, $user, $pass, $charset);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'init_db') {
            $sqlPath = __DIR__ . '/schema.sql';
            if (!is_file($sqlPath)) {
                throw new RuntimeException('Файл schema.sql не знайдено.');
            }
            $pdo->exec((string) file_get_contents($sqlPath));
            $cache->clearAll();
            $message = "Базу даних '{$dbname}', таблицю 'recipes' та індекс 'idx_recipes_cook_time' успішно створено!";
            $messageType = 'success';
        } elseif ($action === 'create_index') {
            $pdo->exec("USE `{$dbname}`");
            $pdo->exec("CREATE INDEX `idx_recipes_cook_time` ON `recipes` (`cook_time_min`)");
            $cache->clearAll();
            $message = "Індекс idx_recipes_cook_time на стовпець cook_time_min створено!";
            $messageType = 'success';
        } elseif ($action === 'drop_index') {
            $pdo->exec("USE `{$dbname}`");
            $pdo->exec("DROP INDEX `idx_recipes_cook_time` ON `recipes`");
            $cache->clearAll();
            $message = "Індекс idx_recipes_cook_time видалено (тепер можна перевірити EXPLAIN без індексу).";
            $messageType = 'info';
        } elseif ($action === 'seed_benchmark') {
            $pdo->exec("USE `{$dbname}`");
            $stmt = $pdo->prepare(
                'INSERT INTO recipes (title, ingredients, cook_time_min, servings, difficulty, is_vegan, substitutions)
                 VALUES (:t, :i, :c, :s, :d, :v, :sub)'
            );
            $bases = [
                ['Борщ домашній', 'Яловичина: 350, Буряк: 200, Капуста: 180, Картопля: 250', 85, 6, 'Складний', 0, null],
                ['Овочеве рагу', 'Картопля: 300, Кабачок: 200, Морква: 100, Перець: 100', 30, 4, 'Легкий', 1, 'Вершкове масло → оливкова олія'],
                ['Паста з песто', 'Спагеті: 200, Соус песто: 60, Сир Пармезан: 40, Томати чері: 100', 15, 2, 'Легкий', 0, null],
                ['Вівсянка з бананом', 'Вівсяні пластівці: 80, Рослинне молоко: 200, Банан: 100, Мед: 15', 10, 1, 'Легкий', 1, 'Молоко → вівсяне молоко'],
                ['Крем-суп грибний', 'Печериці: 350, Картопля: 200, Цибуля: 80, Вершки: 100', 40, 4, 'Середній', 0, null],
            ];
            for ($k = 1; $k <= 100; $k++) {
                $sample = $bases[$k % count($bases)];
                $stmt->execute([
                    ':t'   => "{$sample[0]} (партія #{$k})",
                    ':i'   => $sample[1],
                    ':c'   => max(5, $sample[2] + (($k % 15) - 7)),
                    ':s'   => $sample[3],
                    ':d'   => $sample[4],
                    ':v'   => $sample[5],
                    ':sub' => $sample[6],
                ]);
            }
            $cache->clearAll();
            $message = 'Додано 100 тестових записів для навантажувального тестування Apache Bench (ab)!';
            $messageType = 'success';
        }
    }

    // Перевірка стану БД, індексу та виконання EXPLAIN
    $dbExists = (bool) $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbname))->fetchColumn();
    if ($dbExists) {
        $pdo->exec("USE `{$dbname}`");
        $tableExists = (bool) $pdo->query("SHOW TABLES LIKE 'recipes'")->fetchColumn();
        if ($tableExists) {
            $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
            $idxRows = $pdo->query("SHOW INDEX FROM recipes WHERE Key_name = 'idx_recipes_cook_time'")->fetchAll();
            $indexExists = count($idxRows) > 0;
            $explainResult = $pdo->query("EXPLAIN SELECT * FROM recipes WHERE cook_time_min <= 20")->fetchAll();
        }
    }
} catch (Throwable $e) {
    $message = 'Помилка: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Майстер БД та індексів — Практична №7</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 920px;">
        <header class="header">
            <div class="header-content">
                <span class="header-icon">⚙️</span>
                <div>
                    <span class="header-badge">Практична №7 · БД, Індекси та EXPLAIN</span>
                    <h1>Налаштування бази даних та експериментів</h1>
                    <p class="subtitle">Ініціалізація <code>practicum7</code>, керування індексом <code>idx_recipes_cook_time</code> та генерація даних для <code>ab</code>.</p>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2 class="section-title">1. Керування базою даних та індексом</h2>
            <p style="margin-bottom: 16px; color: var(--gray-600);">
                Поточний стан: записів у таблиці <code>recipes</code> — <strong><?= $totalRows ?></strong>;
                індекс <code>idx_recipes_cook_time</code> —
                <strong style="color: <?= $indexExists ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= $indexExists ? 'АКТИВНИЙ' : 'ВІДСУТНІЙ' ?>
                </strong>.
            </p>

            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <form method="POST">
                    <input type="hidden" name="action" value="init_db">
                    <button type="submit" class="btn btn-primary">🔄 Переініціалізувати БД (schema.sql)</button>
                </form>

                <?php if (!$indexExists): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_index">
                        <button type="submit" class="btn btn-success">⚡ Створити індекс idx_recipes_cook_time</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="drop_index">
                        <button type="submit" class="btn btn-secondary">🗑️ Видалити індекс (для EXPLAIN «до»)</button>
                    </form>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="seed_benchmark">
                    <button type="submit" class="btn btn-outline">📈 Додати +100 записів для тесту ab</button>
                </form>

                <a href="index.php" class="btn btn-primary" style="margin-left: auto;">← Повернутися до застосунку</a>
            </div>
        </section>

        <?php if (is_array($explainResult) && $explainResult !== []): ?>
            <section class="card">
                <h2 class="section-title">2. Результат <code>EXPLAIN SELECT * FROM recipes WHERE cook_time_min &lt;= 20</code></h2>
                <p style="margin-bottom: 12px; font-size: 0.9rem; color: var(--gray-600);">
                    Порівняйте колонки <strong>type</strong> та <strong>rows</strong> до створення індексу (<code>ALL</code> — повне сканування) і після (<code>range</code> — використання індексу <code>idx_recipes_cook_time</code>).
                </p>
                <div class="table-wrapper">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>id</th>
                                <th>select_type</th>
                                <th>table</th>
                                <th>type</th>
                                <th>possible_keys</th>
                                <th>key</th>
                                <th>rows</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($explainResult as $exp): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($exp['id'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($exp['select_type'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($exp['table'] ?? '')) ?></td>
                                    <td><strong><?= htmlspecialchars((string) ($exp['type'] ?? '')) ?></strong></td>
                                    <td><?= htmlspecialchars((string) ($exp['possible_keys'] ?? 'NULL')) ?></td>
                                    <td><strong><?= htmlspecialchars((string) ($exp['key'] ?? 'NULL')) ?></strong></td>
                                    <td><strong><?= htmlspecialchars((string) ($exp['rows'] ?? '')) ?></strong></td>
                                    <td><?= htmlspecialchars((string) ($exp['Extra'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
