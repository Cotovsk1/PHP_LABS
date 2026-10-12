<?php
declare(strict_types=1);

// ============================================================================
// Практична робота №3: ООП у PHP — Варіант 6: Кулінарна книга
// ============================================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Крок 6: підключення класів і бібліотеки функцій
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/classes/Recipe.php';
require_once __DIR__ . '/classes/VeganRecipe.php';
require_once __DIR__ . '/classes/Cookbook.php';

// Створення об'єктів: базового і похідного класів
$cookbook = new Cookbook();

$cookbook->addRecipe(new Recipe(
    'Український червоний борщ',
    ['Яловичина' => 400, 'Буряк' => 250, 'Капуста' => 200, 'Картопля' => 300, 'Сметана' => 60],
    90,
    6
));
$cookbook->addRecipe(new Recipe(
    'Картопляні деруни',
    ['Картопля' => 600, 'Цибуля' => 80, 'Борошно' => 60, 'Яйце' => 50, 'Сметана' => 80],
    35,
    4
));
$cookbook->addRecipe(new Recipe(
    'Класична паста Карбонара',
    ['Спагеті' => 200, 'Бекон' => 120, 'Яєчні жовтки' => 60, 'Сир Пекоріно' => 50],
    25,
    2
));
$cookbook->addRecipe(new VeganRecipe(
    'Веганські панкейки',
    ['Борошно' => 200, 'Рослинне молоко' => 300, 'Банан' => 120, 'Кленовий сироп' => 40],
    15,
    3,
    ['Молоко' => 'рослинне молоко', 'Яйце' => 'пюре з банана']
));
$cookbook->addRecipe(new VeganRecipe(
    'Тости з авокадо',
    ['Цільнозерновий хліб' => 80, 'Авокадо' => 150, 'Лимонний сік' => 10],
    10,
    1,
    ['Яйце пашот' => 'тофу']
));

// Параметри з форми (GET): пошук за інгредієнтом та одиниця виміру
$query = trim((string) ($_GET['q'] ?? ''));
$unit  = ($_GET['unit'] ?? 'g') === 'oz' ? 'oz' : 'g';

$found    = $cookbook->findByIngredient($query);
$shortest = $cookbook->shortestCookTime();

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична №3 — Кулінарна книга (ООП)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-container">

    <header class="header">
        <div class="header-content">
            <div class="header-icon">📖</div>
            <div>
                <span class="header-badge">Практична №3 · Варіант 6</span>
                <h1>Кулінарна книга: класи та успадкування</h1>
                <p class="subtitle">
                    <code>Recipe</code> → <code>VeganRecipe</code>, менеджер <code>Cookbook</code>,
                    бібліотека <code>lib/functions.php</code>.
                </p>
            </div>
        </div>
    </header>

    <section>
        <div class="stats-grid">
            <div class="stat-card primary-stat">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?= $cookbook->count() ?></div>
                <div class="stat-label">Рецептів у книзі</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value" style="font-size:1.2rem">
                    <?= $shortest ? e($shortest->getTitle()) : '—' ?>
                </div>
                <div class="stat-label">
                    Найшвидший рецепт<?= $shortest ? ' (' . e(formatCookTime($shortest->getCookTimeMin())) . ')' : '' ?>
                </div>
            </div>
        </div>
    </section>

    <form class="toolbar" method="get">
        <input type="text" name="q" value="<?= e($query) ?>" placeholder="Знайти за інгредієнтом, напр. «борошно»">
        <select name="unit">
            <option value="g"  <?= $unit === 'g'  ? 'selected' : '' ?>>Грами</option>
            <option value="oz" <?= $unit === 'oz' ? 'selected' : '' ?>>Унції</option>
        </select>
        <button type="submit">Застосувати</button>
    </form>

    <section>
        <div class="section-header-flex">
            <h2 class="section-title">Рецепти</h2>
            <span class="count-badge">Знайдено: <?= count($found) ?></span>
        </div>

        <div class="table-wrapper">
            <?php if ($found === []): ?>
                <p class="empty-note">За інгредієнтом «<?= e($query) ?>» нічого не знайдено.</p>
            <?php else: ?>
            <table class="styled-table">
                <thead>
                <tr>
                    <th>№</th>
                    <th>Тип</th>
                    <th>Назва</th>
                    <th>Час</th>
                    <th>Інгредієнти</th>
                    <th>getInfo()</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($found as $i => $recipe): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <span class="badge <?= $recipe instanceof VeganRecipe ? 'badge-vegan' : 'badge-base' ?>">
                                <?= e($recipe->getType()) ?>
                            </span>
                        </td>
                        <td><strong><?= e($recipe->getTitle()) ?></strong></td>
                        <td><?= e(formatCookTime($recipe->getCookTimeMin())) ?></td>
                        <td class="info-line"><?= e(formatIngredientsList($recipe->getIngredients(), $unit)) ?></td>
                        <td class="info-line"><?= e($recipe->getInfo()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        Практична робота №3 · Варіант 6 · PHP <?= e(PHP_VERSION) ?>
    </footer>
</div>
</body>
</html>
