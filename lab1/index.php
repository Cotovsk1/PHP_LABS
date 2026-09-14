<?php
declare(strict_types=1);

// ============================================================================
// Практична робота: Знайомство з базовим синтаксисом PHP
// Варіант 6: Кулінарна книга (рецепти, інгредієнти)
// ============================================================================

// Крок 7: Налаштування відображення помилок на час розробки
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ----------------------------------------------------------------------------
// Крок 2: Оголосити масив даних свого домену
// Поля: title, cookTimeMin, servings, difficulty (+ ingredients, category)
// ----------------------------------------------------------------------------
$recipes = [
    [
        'title'       => 'Український червоний борщ',
        'cookTimeMin' => 90,
        'servings'    => 6,
        'difficulty'  => 'Складний',
        'category'    => 'Перші страви',
        'icon'        => '🍲',
        'ingredients' => ['Яловичина', 'Буряк', 'Капуста', 'Картопля', 'Морква', 'Квасоля', 'Сметана']
    ],
    [
        'title'       => 'Картопляні деруни з грибами',
        'cookTimeMin' => 35,
        'servings'    => 4,
        'difficulty'  => 'Середній',
        'category'    => 'Гарячі страви',
        'icon'        => '🥔',
        'ingredients' => ['Картопля', 'Печериці', 'Цибуля ріпчаста', 'Сметана', 'Борошно', 'Яйце']
    ],
    [
        'title'       => 'Салат Цезар з хрусткою куркою',
        'cookTimeMin' => 20,
        'servings'    => 2,
        'difficulty'  => 'Легкий',
        'category'    => 'Салати',
        'icon'        => '🥗',
        'ingredients' => ['Листя салату Ромен', 'Куряче філе', 'Сир Пармезан', 'Сухарики', 'Соус Цезар']
    ],
    [
        'title'       => 'Пишні панкейки з медом та ягодами',
        'cookTimeMin' => 15,
        'servings'    => 3,
        'difficulty'  => 'Легкий',
        'category'    => 'Сніданки та десерти',
        'icon'        => '🥞',
        'ingredients' => ['Борошно пшеничне', 'Молоко', 'Яйце куряче', 'Вершкове масло', 'Мед', 'Свіжі ягоди']
    ],
    [
        'title'       => 'Класична паста Карбонара',
        'cookTimeMin' => 25,
        'servings'    => 2,
        'difficulty'  => 'Середній',
        'category'    => 'Основні страви',
        'icon'        => '🍝',
        'ingredients' => ['Спагеті', 'Панчета або бекон', 'Яєчні жовтки', 'Сир Пекоріно Романо', 'Чорний перець']
    ],
    [
        'title'       => 'Тости з авокадо та яйцем пашот',
        'cookTimeMin' => 10,
        'servings'    => 1,
        'difficulty'  => 'Легкий',
        'category'    => 'Сніданки',
        'icon'        => '🥑',
        'ingredients' => ['Цільнозерновий хліб', 'Стигле авокадо', 'Яйце куряче', 'Лимонний сік', 'Пластівці перцю']
    ],
];

// ----------------------------------------------------------------------------
// Крок 3: Написати функцію форматування
// Типізовані параметри і значення, що повертається
// ----------------------------------------------------------------------------
/**
 * Форматує запис рецепта у вигляді короткого інформативного рядка.
 *
 * @param array $recipe Асоціативний масив рецепта
 * @return string Відформатований рядок опису
 */
function formatRecipe(array $recipe): string {
    return "{$recipe['title']} — {$recipe['servings']} порц. ({$recipe['cookTimeMin']} хв, {$recipe['difficulty']})";
}

// ----------------------------------------------------------------------------
// Крок 4: Додати умовну логіку
// Умова варіанта: якщо cookTimeMin <= 20 - мітка «Швидкий рецепт»
// ----------------------------------------------------------------------------
/**
 * Визначає мітку тривалості приготування рецепта на основі умови.
 *
 * @param int $cookTimeMin Час приготування у хвилинах
 * @return array Масив із текстовою міткою, css-класом та ознакою швидкості
 */
function getRecipeBadge(int $cookTimeMin): array {
    if ($cookTimeMin <= 20) {
        return [
            'label' => '⚡ Швидкий рецепт',
            'class' => 'badge-quick',
            'isQuick' => true,
        ];
    } elseif ($cookTimeMin <= 45) {
        return [
            'label' => '⏱ Помірний час',
            'class' => 'badge-medium',
            'isQuick' => false,
        ];
    } else {
        return [
            'label' => '⏳ Тривале готування',
            'class' => 'badge-slow',
            'isQuick' => false,
        ];
    }
}

// ----------------------------------------------------------------------------
// Крок 6: Обчислити агрегатний показник
// Агрегат варіанта: середній час приготування по всіх рецептах
// ----------------------------------------------------------------------------
$totalRecipes    = count($recipes);
$cookTimesList   = array_column($recipes, 'cookTimeMin');
$totalCookTime   = array_sum($cookTimesList);
$averageCookTime = $totalRecipes > 0 ? round($totalCookTime / $totalRecipes, 1) : 0.0;

// Додаткові допоміжні агрегати для повноти звіту
$quickRecipesCount = count(array_filter($recipes, static fn(array $r): bool => $r['cookTimeMin'] <= 20));
$minCookTime       = !empty($cookTimesList) ? min($cookTimesList) : 0;
$maxCookTime       = !empty($cookTimesList) ? max($cookTimesList) : 0;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кулінарна книга — Варіант 6 (Практикум PHP)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-container">
        <!-- Шапка сторінки -->
        <header class="header">
            <div class="header-content">
                <div class="header-icon">📖</div>
                <div>
                    <span class="header-badge">Варіант №6 • Практична робота</span>
                    <h1>Кулінарна книга рецептів</h1>
                    <p class="subtitle">Базовий синтаксис PHP: змінні, асоціативні масиви, функції, умовні конструкції та агрегація даних</p>
                </div>
            </div>
        </header>

        <!-- Крок 6: Блок агрегатних показників -->
        <section class="stats-section">
            <h2 class="section-title">📊 Агрегатні показники домену</h2>
            <div class="stats-grid">
                <div class="stat-card primary-stat">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-value"><?= $averageCookTime ?> <span class="stat-unit">хв</span></div>
                    <div class="stat-label">Середній час приготування (Агрегат)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= $totalRecipes ?></div>
                    <div class="stat-label">Всього рецептів у базі</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-value"><?= $quickRecipesCount ?></div>
                    <div class="stat-label">Швидких рецептів (≤ 20 хв)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⌛</div>
                    <div class="stat-value"><?= $minCookTime ?> - <?= $maxCookTime ?> <span class="stat-unit">хв</span></div>
                    <div class="stat-label">Діапазон часу (хв - макс)</div>
                </div>
            </div>
        </section>

        <!-- Крок 5: Виведення даних через картки (foreach) -->
        <section class="recipes-section">
            <div class="section-header-flex">
                <h2 class="section-title">🍳 Каталог рецептів (Картки)</h2>
                <span class="count-badge"><?= $totalRecipes ?> рецептів</span>
            </div>

            <div class="cards-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php 
                        // Крок 4: Отримання умовної мітки
                        $badge = getRecipeBadge($recipe['cookTimeMin']); 
                    ?>
                    <article class="recipe-card <?= $badge['isQuick'] ? 'is-quick-card' : '' ?>">
                        <div class="card-header">
                            <span class="recipe-icon"><?= htmlspecialchars($recipe['icon']) ?></span>
                            <span class="badge <?= $badge['class'] ?>"><?= htmlspecialchars($badge['label']) ?></span>
                        </div>

                        <div class="card-category"><?= htmlspecialchars($recipe['category']) ?></div>
                        <h3 class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></h3>

                        <!-- Крок 3: Використання функції форматування formatRecipe() -->
                        <div class="formatted-summary">
                            <strong>Опис:</strong> <?= htmlspecialchars(formatRecipe($recipe)) ?>
                        </div>

                        <div class="recipe-meta">
                            <div class="meta-item">
                                <span class="meta-icon">⏱</span>
                                <div>
                                    <span class="meta-label">Час:</span>
                                    <span class="meta-value"><?= $recipe['cookTimeMin'] ?> хв</span>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">👥</span>
                                <div>
                                    <span class="meta-label">Порції:</span>
                                    <span class="meta-value"><?= $recipe['servings'] ?></span>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📈</span>
                                <div>
                                    <span class="meta-label">Складність:</span>
                                    <span class="meta-value"><?= htmlspecialchars($recipe['difficulty']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Інгредієнти рецепта -->
                        <div class="ingredients-block">
                            <span class="ingredients-title">Інгредієнти:</span>
                            <div class="ingredients-tags">
                                <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                    <span class="ingredient-tag"><?= htmlspecialchars($ingredient) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Крок 5: Додаткова зведена HTML-таблиця -->
        <section class="table-section">
            <h2 class="section-title">📋 Зведена таблиця рецептів</h2>
            <div class="table-wrapper">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Назва рецепта</th>
                            <th>Час (хв)</th>
                            <th>Порції</th>
                            <th>Складність</th>
                            <th>Умовна мітка (Крок 4)</th>
                            <th>Форматований вивід (Крок 3)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipes as $index => $recipe): ?>
                            <?php $badge = getRecipeBadge($recipe['cookTimeMin']); ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($recipe['icon']) ?> <?= htmlspecialchars($recipe['title']) ?></strong>
                                    <div class="small-category"><?= htmlspecialchars($recipe['category']) ?></div>
                                </td>
                                <td>
                                    <span class="time-pill <?= $badge['isQuick'] ? 'time-quick' : '' ?>">
                                        <?= $recipe['cookTimeMin'] ?> хв
                                    </span>
                                </td>
                                <td><?= $recipe['servings'] ?></td>
                                <td><?= htmlspecialchars($recipe['difficulty']) ?></td>
                                <td>
                                    <span class="badge <?= $badge['class'] ?>">
                                        <?= htmlspecialchars($badge['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <code class="code-preview"><?= htmlspecialchars(formatRecipe($recipe)) ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-footer-row">
                            <td colspan="2"><strong>Середнє значення (Агрегат):</strong></td>
                            <td colspan="5">
                                <strong><?= $averageCookTime ?> хв</strong> (середній час по <?= $totalRecipes ?> рецептах)
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <!-- Підвал -->
        <footer class="footer">
            <p>Практикум з PHP • Виконано згідно з вимогами Варіанта №6 (Кулінарна книга)</p>
            <p class="footer-sub">Згенеровано скриптом <code>index.php</code> на PHP <?= PHP_VERSION ?></p>
        </footer>
    </div>
</body>
</html>
