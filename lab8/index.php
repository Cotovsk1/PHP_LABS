<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';

Security::initSession();
$csrfToken = Security::getCsrfToken();
$currentUser = Security::currentUser();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Security::e($csrfToken) ?>">
    <title>Кулінарна книга — Фінальний застосунок (ЛР №1–8)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <!-- Шапка застосунку + блок сесійної авторизації -->
        <header class="header">
            <div class="header-content">
                <span class="header-icon">🛡️</span>
                <div>
                    <span class="header-badge">Практичні №1–8 · Варіант №6 · Захищена версія</span>
                    <h1>Кулінарна книга (Full-Stack + Аудит безпеки)</h1>
                    <p class="subtitle">
                        SQLi-захист (<code>prepare</code> + екранування <code>LIKE</code>), XSS-екранування (<code>ingredients</code>, <code>steps</code>),
                        CSRF-токени (<code>403</code>), POST-видалення та безпечне логування винятків.
                    </p>
                </div>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php if ($currentUser): ?>
                    <span style="font-size: 0.85rem; background: rgba(255,255,255,0.12); padding: 6px 12px; border-radius: 999px;">
                        👤 <?= Security::e($currentUser['username']) ?> (<?= Security::e($currentUser['role']) ?>)
                    </span>
                    <form method="POST" action="logout.php" style="margin: 0;">
                        <?= Security::csrfInput() ?>
                        <button type="submit" class="btn btn-sm btn-secondary">Вийти</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-primary">🔐 Увійти</a>
                    <a href="register.php" class="btn btn-sm btn-secondary">Реєстрація</a>
                <?php endif; ?>
                <a href="setup.php" class="btn btn-sm btn-secondary">⚙️ БД</a>
            </div>
        </header>

        <!-- Статус захисту ЛР №8 -->
        <section class="profiler-bar card">
            <div class="profiler-header" style="margin-bottom: 0;">
                <div>
                    <strong>🔒 Статус захисту безпеки (Практична №8)</strong>
                    <span class="profiler-sub">
                        Активний CSRF-токен сесії: <code><?= Security::e(substr($csrfToken, 0, 16)) ?>…</code> ·
                        Запити без токена відхиляються кодом <code>403 Forbidden</code> ·
                        <code>GET ?action=delete</code> заблоковано (<code>405</code>)
                    </span>
                </div>
                <div class="profiler-metrics" style="min-width: 340px;">
                    <div class="metric-pill">
                        <span class="metric-label">SQL-запитів / Час PHP:</span>
                        <strong id="metricPerf">—</strong>
                    </div>
                    <div class="metric-pill">
                        <span class="metric-label">Кеш (TTL 45с):</span>
                        <strong id="metricCache">—</strong>
                    </div>
                </div>
            </div>
        </section>

        <!-- Агрегати ЛР №1–7 -->
        <section class="stats-grid">
            <div class="stat-card primary-stat">
                <div class="stat-icon">⚡</div>
                <div class="stat-value" id="statFastestTime">—</div>
                <div class="stat-label">Найшвидший рецепт (<code>shortestCookTime</code>)</div>
                <div class="stat-sub" id="statFastestTitle">Завантаження...</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value" id="statAvgTime">—</div>
                <div class="stat-label">Середній час приготування</div>
                <div class="stat-sub">Агрегат по каталогу</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-value" id="statQuickCount">—</div>
                <div class="stat-label">Швидких рецептів (≤ 20 хв)</div>
                <div class="stat-sub">Бейдж «⚡ Швидкий рецепт»</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value" id="statTotalCount">—</div>
                <div class="stat-label">Всього рецептів у БД</div>
                <div class="stat-sub">Захищено від XSS та SQLi</div>
            </div>
        </section>

        <!-- Фільтри та перемикач одиниць localStorage -->
        <section class="card toolbar-card">
            <div class="toolbar-grid">
                <div class="toolbar-field">
                    <label for="searchInput" class="form-label">🔍 Пошук за інгредієнтом (<code>LIKE</code> захищено від SQLi)</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Спробуйте ввести: буряк або ' OR '1'='1">
                </div>
                <div class="toolbar-field">
                    <label for="maxTimeFilter" class="form-label">⏱ Макс. час (індекс <code>cook_time_min</code>)</label>
                    <input type="number" id="maxTimeFilter" class="form-control" min="1" max="1440" placeholder="Усі (або напр. 20)">
                </div>
                <div class="toolbar-field">
                    <label class="form-label">⚖️ Одиниці ваги (<code>localStorage</code>)</label>
                    <div class="unit-toggle">
                        <button type="button" class="unit-btn active" data-unit="g">Грами (г)</button>
                        <button type="button" class="unit-btn" data-unit="oz">Унції (oz)</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Захищена форма додавання / редагування рецепта -->
        <section class="card">
            <div class="section-header-flex">
                <h2 class="section-title" id="formHeading">➕ Додати новий рецепт (із CSRF-захистом)</h2>
                <button type="button" id="cancelEditBtn" class="btn btn-sm btn-secondary" style="display: none;">Скасувати редагування</button>
            </div>

            <div id="formAlert" class="alert alert-error" style="display: none; margin-bottom: 16px;"></div>

            <form id="recipeForm" novalidate>
                <?= Security::csrfInput() ?>
                <input type="hidden" id="editRecipeId" value="">

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="titleInput" class="form-label">Назва страви *</label>
                        <input type="text" id="titleInput" class="form-control" placeholder="Наприклад: Овочевий салат" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label for="cookTimeInput" class="form-label">Час приготування (хв) *</label>
                        <input type="number" id="cookTimeInput" class="form-control" placeholder="15" min="1" max="1440" required>
                    </div>
                    <div class="form-group">
                        <label for="servingsInput" class="form-label">Порції / Складність</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" id="servingsInput" class="form-control" value="2" min="1" max="50">
                            <select id="difficultyInput" class="form-control">
                                <option value="Легкий">Легкий</option>
                                <option value="Середній" selected>Середній</option>
                                <option value="Складний">Складний</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ingredientsInput" class="form-label">
                        Інгредієнти * <span class="hint-inline">(XSS-перевірка: спробуйте <code>&lt;script&gt;alert(1)&lt;/script&gt;</code> — виведеться безпечно як текст)</span>
                    </label>
                    <input type="text" id="ingredientsInput" class="form-control" placeholder="Томати: 200, Огірки: 150, Оливкова олія: 20" required>
                </div>

                <div class="form-group">
                    <label for="stepsInput" class="form-label">
                        Кроки приготування (<code>steps</code>) *
                    </label>
                    <textarea id="stepsInput" class="form-control" rows="2" placeholder="1. Наріжте овочі. 2. Заправте олією та перемішайте." required></textarea>
                </div>

                <div class="form-row-2" style="align-items: center;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="isVeganInput">
                        <span>🌱 Веганський рецепт (<code>VeganRecipe</code>)</span>
                    </label>
                    <div id="substitutionsGroup" style="display: none;">
                        <input type="text" id="substitutionsInput" class="form-control" placeholder="Заміни (напр.: Сметана -> соєвий йогурт)">
                    </div>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Зберегти рецепт</button>
                    <button type="button" id="resetDraftBtn" class="btn btn-secondary">Очистити форму та чернетку</button>
                </div>
            </form>
        </section>

        <!-- Каталог рецептів -->
        <section class="card">
            <div class="section-header-flex">
                <h2 class="section-title">📋 Каталог рецептів</h2>
                <span class="count-badge" id="tableCountBadge">0 записів</span>
            </div>
            <div id="resultsContainer" class="table-wrapper">
                <p style="padding: 20px;">Завантаження рецептів...</p>
            </div>
        </section>

        <footer class="footer">
            Практична робота №8 • Фінальний захищений застосунок курсу (ЛР №1–8) • Варіант №6: Кулінарна книга
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>
