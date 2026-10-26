<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Отримання пошукового запиту (Вимога варіанта 6: findByIngredient)
$search = trim($_GET['search'] ?? '');
$isSearchActive = ($search !== '');

if ($isSearchActive) {
    $recipes = findByIngredient($pdo, $search);
} else {
    // Крок 3: SELECT * FROM recipes через fetchAll()
    $recipes = getAllRecipes($pdo);
}

// Вимога варіанта 6: shortestCookTime()
$fastestRecipe = shortestCookTime($pdo);

// Повідомлення для користувача (Flash messages)
$msg = $_GET['msg'] ?? null;
$flashMessages = [
    'added'     => ['type' => 'success', 'text' => '✅ Рецепт успішно додано до бази даних! (INSERT)'],
    'updated'   => ['type' => 'success', 'text' => '✅ Дані рецепта успішно оновлено! (UPDATE)'],
    'deleted'   => ['type' => 'success', 'text' => '🗑️ Рецепт успішно видалено з бази даних! (DELETE)'],
    'not_found' => ['type' => 'error',   'text' => '⚠️ Зазначений рецепт не знайдено.'],
    'error'     => ['type' => 'error',   'text' => '❌ Виникла помилка: ' . htmlspecialchars($_GET['detail'] ?? 'Невідома помилка')],
];
$currentFlash = ($msg && isset($flashMessages[$msg])) ? $flashMessages[$msg] : null;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична робота №4 · Варіант №6 (Кулінарна книга)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <!-- Шапка сторінки -->
        <header class="header">
            <div class="header-content">
                <span class="header-icon">📖</span>
                <div>
                    <span class="header-badge">Практична робота №4 · PDO / MySQL</span>
                    <h1>Кулінарна книга (рецепти, інгредієнти)</h1>
                    <p class="subtitle">
                        Демонстрація взаємодії з базою даних через <strong>PDO</strong>: 
                        виконання CRUD-операцій (<code>SELECT</code>, <code>INSERT</code>, <code>UPDATE</code>, <code>DELETE</code>) 
                        та захист від SQL-ін'єкцій за допомогою <code>prepared statements</code>.
                    </p>
                </div>
            </div>
        </header>

        <!-- Повідомлення про статус операції -->
        <?php if ($currentFlash): ?>
            <div class="alert alert-<?= $currentFlash['type'] ?>">
                <?= $currentFlash['text'] ?>
            </div>
        <?php endif; ?>

        <!-- Спеціальні вибірки варіанта 6: Картка найшвидшого рецепта та статистика -->
        <section class="stats-grid">
            <div class="stat-card primary-stat">
                <span class="stat-icon">⚡</span>
                <span class="stat-label">Найшвидший рецепт (shortestCookTime)</span>
                <?php if ($fastestRecipe): ?>
                    <span class="stat-value"><?= (int)$fastestRecipe['cook_time_min'] ?> хв</span>
                    <div style="font-weight: 600; color: var(--gray-800); margin-top: 4px;">
                        «<?= htmlspecialchars($fastestRecipe['title'], ENT_QUOTES, 'UTF-8') ?>»
                    </div>
                <?php else: ?>
                    <span class="stat-value">—</span>
                    <div style="color: var(--gray-600); font-size: 0.85rem;">База даних порожня</div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <span class="stat-icon">📊</span>
                <span class="stat-label">Всього знайдено в базі даних</span>
                <span class="stat-value"><?= count($recipes) ?></span>
                <div style="color: var(--gray-600); font-size: 0.85rem;">
                    <?= $isSearchActive ? 'За фільтром: «' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '»' : 'Усі збережені записи' ?>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🛡️</span>
                <span class="stat-label">Режим безпеки PDO</span>
                <span class="stat-value" style="font-size: 1.25rem; color: #16a34a; margin-top: 4px;">Prepared Statements</span>
                <div style="color: var(--gray-600); font-size: 0.85rem;">
                    Захист від SQL-ін'єкцій активовано
                </div>
            </div>
        </section>

        <!-- Панель дій та пошуку -->
        <section class="action-bar-card card">
            <div class="action-bar">
                <div class="search-form-wrapper">
                    <!-- Пошук за інгредієнтом (Вимога варіанта 6: findByIngredient) -->
                    <form action="index.php" method="GET" class="search-form">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="Пошук за інгредієнтом (наприклад: яйце, картопля, сир)..." 
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <button type="submit" class="btn btn-primary">🔍 Знайти</button>
                        <?php if ($isSearchActive): ?>
                            <a href="index.php" class="btn btn-secondary">Скинути</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="main-buttons">
                    <a href="add.php" class="btn btn-success">
                        ➕ Додати новий рецепт
                    </a>
                    <a href="setup.php" class="btn btn-outline" title="Налаштування бази даних">
                        ⚙️ БД Майстер
                    </a>
                </div>
            </div>
        </section>

        <!-- Основна таблиця рецептів (Крок 3: SELECT) -->
        <main class="card">
            <div class="section-header-flex">
                <h2 class="section-title">
                    <?= $isSearchActive ? 'Результати пошуку за інгредієнтом: «' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '»' : 'Каталог усіх кулінарних рецептів' ?>
                </h2>
                <span class="count-badge">Записів: <?= count($recipes) ?></span>
            </div>

            <?php if (empty($recipes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🍽️</div>
                    <h3>Жодного рецепта не знайдено</h3>
                    <p>
                        <?= $isSearchActive ? 'За вказаним інгредієнтом нічого не знайдено. Спробуйте змінити критерій пошуку.' : 'Таблиця рецептів порожня. Додайте свій перший рецепт або ініціалізуйте базу тестовими даними.' ?>
                    </p>
                    <div style="margin-top: 16px; display: flex; gap: 12px; justify-content: center;">
                        <?php if ($isSearchActive): ?>
                            <a href="index.php" class="btn btn-secondary">Скинути фільтр</a>
                        <?php endif; ?>
                        <a href="add.php" class="btn btn-primary">➕ Додати рецепт</a>
                        <a href="setup.php" class="btn btn-outline">Ініціалізувати демо-дані</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 250px;">Назва рецепта</th>
                                <th>Інгредієнти</th>
                                <th style="width: 170px;">Час приготування</th>
                                <th style="width: 180px; text-align: center;">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipes as $row): ?>
                                <?php $badge = getRecipeBadge((int)$row['cook_time_min']); ?>
                                <tr>
                                    <td><strong>#<?= (int)$row['id'] ?></strong></td>
                                    <td>
                                        <div class="recipe-name"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <div class="ingredients-list">
                                            <?= htmlspecialchars($row['ingredients'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cook-time">
                                            <strong><?= (int)$row['cook_time_min'] ?></strong> хв
                                            <span class="badge <?= $badge['class'] ?>">
                                                <?= $badge['label'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <!-- Крок 5: Посилання «Редагувати» (UPDATE) -->
                                            <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-edit">
                                                ✏️ Змінити
                                            </a>

                                            <!-- Крок 6: Кнопка/форма «Видалити» з JavaScript confirm (DELETE) -->
                                            <form action="delete.php" method="POST" onsubmit="return confirm('Ви впевнені, що бажаєте безповоротно видалити рецепт «<?= htmlspecialchars(addslashes($row['title']), ENT_QUOTES, 'UTF-8') ?>»?');" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-delete">
                                                    🗑️ Видалити
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>

        <!-- Крок 7: Інформаційний блок перевірки захисту від SQL-ін'єкцій -->
        <section class="card security-card">
            <h3>🛡️ Крок 7. Перевірка та демонстрація захисту від SQL-ін'єкцій</h3>
            <p>
                Завдяки використанню <strong>PDO Prepared Statements</strong> (<code>prepare()</code> + <code>execute()</code>) 
                усі значення передаються СУБД як окремі дані, а не інтерпретуються як код запиту.
            </p>
            <div class="security-grid">
                <div class="code-compare-card bad">
                    <span class="code-title">❌ Небезпечно (конкатенація рядків):</span>
                    <pre><code>$sql = "SELECT * FROM recipes WHERE ingredients LIKE '%" . $_GET['search'] . "%'";
$pdo->query($sql);
// Вразливість: рядок ' OR '1'='1 може змінити структуру запиту!</code></pre>
                </div>
                <div class="code-compare-card good">
                    <span class="code-title">✅ Безпечно (підготовлені запити у нашій програмі):</span>
                    <pre><code>$stmt = $pdo->prepare('SELECT * FROM recipes WHERE ingredients LIKE :pattern');
$stmt->execute([':pattern' => '%' . $_GET['search'] . '%']);
// Будь-які лапки, крапки з комою чи ' OR '1'='1 сприймаються лише як текст!</code></pre>
                </div>
            </div>
            <div style="margin-top: 14px; font-size: 0.9rem; color: var(--gray-700);">
                💡 <em>Спробуйте ввести у форму пошуку або форму додавання рецепта рядок <code>' OR '1'='1</code> або <code>'); DROP TABLE test; --</code> — інтерпретатор PDO коректно екранує всі параметри, виключаючи ймовірність атаки.</em>
            </div>
        </section>

        <!-- Підвал сторінки -->
        <footer class="footer">
            Сучасні технології розробки WEB-застосувань мовою програмування PHP · 3 курс, ФІОТ КПІ<br>
            Практична робота №4 · <strong>Варіант №6: Кулінарна книга</strong>
        </footer>
    </div>
</body>
</html>
