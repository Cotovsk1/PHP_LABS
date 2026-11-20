<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кулінарна книга — Єдиний застосунок (ЛР №1–7)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <!-- Шапка застосунку -->
        <header class="header">
            <div class="header-content">
                <span class="header-icon">📖</span>
                <div>
                    <span class="header-badge">Практичні №1–7 · Варіант №6</span>
                    <h1>Кулінарна книга (Full-Stack + Оптимізація)</h1>
                    <p class="subtitle">
                        ООП (<code>Recipe</code> / <code>VeganRecipe</code>), PDO CRUD, AJAX REST API,
                        <code>localStorage</code> (грами/унції), індекс <code>cook_time_min</code>, усунення N+1 та файловий кеш.
                    </p>
                </div>
            </div>
            <div class="header-actions">
                <a href="setup.php" class="btn btn-secondary">⚙️ БД та EXPLAIN</a>
            </div>
        </header>

        <!-- Панель профайлера ЛР №7 -->
        <section class="profiler-bar card">
            <div class="profiler-header">
                <div>
                    <strong>⚡ Профайлер продуктивності API (ЛР №7)</strong>
                    <span class="profiler-sub">Вимірювання в реальному часі на кожен запит до <code>api.php?resource=recipes</code></span>
                </div>
                <div class="profiler-controls">
                    <label class="mode-switch">
                        <input type="checkbox" id="unoptimizedToggle">
                        <span>Режим «До оптимізації» ($N+1$ без кешу)</span>
                    </label>
                    <button type="button" id="clearCacheBtn" class="btn btn-sm btn-outline">🧹 Очистити кеш</button>
                </div>
            </div>
            <div class="profiler-metrics">
                <div class="metric-pill">
                    <span class="metric-label">Режим:</span>
                    <strong id="metricMode">optimized</strong>
                </div>
                <div class="metric-pill">
                    <span class="metric-label">SQL-запитів:</span>
                    <strong id="metricQueries">—</strong>
                </div>
                <div class="metric-pill">
                    <span class="metric-label">Час виконання PHP:</span>
                    <strong id="metricTime">—</strong>
                </div>
                <div class="metric-pill">
                    <span class="metric-label">Пікова пам'ять:</span>
                    <strong id="metricMemory">—</strong>
                </div>
                <div class="metric-pill">
                    <span class="metric-label">Файловий кеш (TTL 45с):</span>
                    <strong id="metricCache">—</strong>
                </div>
            </div>
        </section>

        <!-- Блок агрегатних показників (ЛР №1 + ЛР №3 + ЛР №4 + ЛР №7) -->
        <section class="stats-grid">
            <div class="stat-card primary-stat">
                <div class="stat-icon">⚡</div>
                <div class="stat-value" id="statFastestTime">—</div>
                <div class="stat-label">Найшвидший рецепт (кешований shortestCookTime)</div>
                <div class="stat-sub" id="statFastestTitle">Завантаження...</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value" id="statAvgTime">—</div>
                <div class="stat-label">Середній час приготування (ЛР №1)</div>
                <div class="stat-sub">Агрегат по поточній вибірці</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-value" id="statQuickCount">—</div>
                <div class="stat-label">Швидких рецептів (≤ 20 хв)</div>
                <div class="stat-sub">Позначка «⚡ Швидкий рецепт»</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value" id="statTotalCount">—</div>
                <div class="stat-label">Всього рецептів у вибірці</div>
                <div class="stat-sub">Об'єкти <code>Recipe</code> та <code>VeganRecipe</code></div>
            </div>
        </section>

        <!-- Панель пошуку, фільтрації за індексованим часом та перемикач одиниць localStorage (ЛР №2, 5, 7) -->
        <section class="card toolbar-card">
            <div class="toolbar-grid">
                <div class="toolbar-field">
                    <label for="searchInput" class="form-label">🔍 Живий пошук за інгредієнтом або назвою</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Наприклад: буряк, картопля, авокадо...">
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

        <!-- Форма додавання / редагування рецепта (ЛР №2 + ЛР №3 + ЛР №4 + ЛР №5) -->
        <section class="card">
            <div class="section-header-flex">
                <h2 class="section-title" id="formHeading">➕ Додати новий рецепт</h2>
                <button type="button" id="cancelEditBtn" class="btn btn-sm btn-secondary" style="display: none;">Скасувати редагування</button>
            </div>

            <div id="formAlert" class="alert alert-error" style="display: none; margin-bottom: 16px;"></div>

            <form id="recipeForm" novalidate>
                <input type="hidden" id="editRecipeId" value="">

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="titleInput" class="form-label">Назва страви *</label>
                        <input type="text" id="titleInput" class="form-control" placeholder="Наприклад: Гарбузовий крем-суп" required minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="cookTimeInput" class="form-label">Час приготування (хв) *</label>
                        <input type="number" id="cookTimeInput" class="form-control" placeholder="20" min="1" max="1440" required>
                    </div>

                    <div class="form-group">
                        <label for="servingsInput" class="form-label">Порції / Складність</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" id="servingsInput" class="form-control" value="2" min="1" max="50" title="Кількість порцій">
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
                        Інгредієнти * <span class="hint-inline">(формат <code>Назва: вага_у_грамах</code> через кому для автоконвертації в унції)</span>
                    </label>
                    <input type="text" id="ingredientsInput" class="form-control" placeholder="Гарбуз: 500, Вершки: 150, Цибуля: 80" required>
                </div>

                <div class="form-row-2" style="align-items: center;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="isVeganInput">
                        <span>🌱 Веганський рецепт (<code>VeganRecipe</code>)</span>
                    </label>

                    <div id="substitutionsGroup" style="display: none;">
                        <input type="text" id="substitutionsInput" class="form-control" placeholder="Заміни (напр.: Молоко → рослинне молоко)">
                    </div>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" id="submitFormBtn">💾 Зберегти рецепт</button>
                    <button type="button" class="btn btn-secondary" id="resetDraftBtn">Очистити форму та чернетку</button>
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
            Практична робота №7 • Об'єднаний застосунок (ЛР №1–7) • Варіант №6: Кулінарна книга • PHP <?= PHP_VERSION ?>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>
