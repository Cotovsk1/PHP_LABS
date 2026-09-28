<?php
declare(strict_types=1);

// ============================================================================
// Практична робота №2: Взаємодія вебзастосунку з користувачем
// Варіант 6: Кулінарна книга (рецепти, інгредієнти)
//
// Вимоги варіанта 6:
// 1. Форма: новий рецепт — title, ingredients, cookTimeMin (+ unit для мір).
// 2. Валідація: cookTimeMin — число більше 0; ingredients — непорожній перелік.
// 3. localStorage: запам'ятати обрану одиницю виміру (грами/унції) для інгредієнтів.
// ============================================================================

// Крок 7: Налаштування виведення помилок під час розробки
error_reporting(E_ALL);
ini_set('display_errors', '1');

$errors = [];
$isSuccess = false;
$submittedData = [];

// Допустимі одиниці виміру згідно зі сценарієм варіанта 6
$allowedUnits = [
    'g'  => 'Грами (г / g)',
    'oz' => 'Унції (унц / oz)',
];

// Початкові значення полів форми
$title = '';
$cookTimeMin = '';
$unit = 'g';
$ingredients = '';

// Допоміжна функція для підрахунку довжини UTF-8 рядка
function str_length(string $str): int {
    if (function_exists('mb_strlen')) {
        return mb_strlen($str, 'UTF-8');
    }
    return (int)preg_match_all('/./us', $str);
}

// ----------------------------------------------------------------------------
// Крок 3: Серверна обробка й валідація
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання та очищення вхідних даних
    $title = trim((string)($_POST['title'] ?? ''));
    $cookTimeMinRaw = trim((string)($_POST['cookTimeMin'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? 'g'));
    $ingredients = trim((string)($_POST['ingredients'] ?? ''));

    // 1. Валідація назви рецепта (title)
    if ($title === '') {
        $errors['title'] = 'Назва рецепта є обов\'язковою.';
    } elseif (str_length($title) < 3) {
        $errors['title'] = 'Назва рецепта повинна містити щонайменше 3 символи.';
    } elseif (str_length($title) > 100) {
        $errors['title'] = 'Назва рецепта занадто довга (максимум 100 символів).';
    }

    // 2. Валідація часу приготування (cookTimeMin)
    // Правило варіанта: cookTimeMin - число більше 0
    if ($cookTimeMinRaw === '') {
        $errors['cookTimeMin'] = 'Час приготування є обов\'язковим полем.';
    } elseif (filter_var($cookTimeMinRaw, FILTER_VALIDATE_INT) === false) {
        $errors['cookTimeMin'] = 'Час приготування має бути цілим числом.';
    } else {
        $cookTimeMinVal = (int)$cookTimeMinRaw;
        if ($cookTimeMinVal <= 0) {
            $errors['cookTimeMin'] = 'Час приготування має бути суворо більшим за 0 хв.';
        } elseif ($cookTimeMinVal > 1440) {
            $errors['cookTimeMin'] = 'Час приготування не може перевищувати 1440 хвилин (24 години).';
        } else {
            $cookTimeMin = (string)$cookTimeMinVal;
        }
    }

    // 3. Валідація одиниці виміру (unit)
    if (!array_key_exists($unit, $allowedUnits)) {
        $errors['unit'] = 'Обрано невідому одиницю виміру.';
    }

    // 4. Валідація інгредієнтів (ingredients)
    // Правило варіанта: ingredients - непорожній перелік (мінімум один інгредієнт)
    if ($ingredients === '') {
        $errors['ingredients'] = 'Перелік інгредієнтів є обов\'язковим.';
    } else {
        // Розбиваємо за рядками або комами
        $rawLines = preg_split('/[\r\n]+|,/', $ingredients);
        $cleanIngredients = [];
        foreach ($rawLines as $item) {
            $trimmed = trim($item);
            if ($trimmed !== '') {
                $cleanIngredients[] = $trimmed;
            }
        }

        if (count($cleanIngredients) === 0) {
            $errors['ingredients'] = 'Необхідно вказати щонайменше один валідний інгредієнт.';
        } else {
            $parsedIngredientsList = $cleanIngredients;
        }
    }

    // Якщо серверна валідація пройдена успішно (Крок 4)
    if (empty($errors)) {
        $isSuccess = true;
        $submittedData = [
            'title'       => $title,
            'cookTimeMin' => (int)$cookTimeMin,
            'unit'        => $unit,
            'unitLabel'   => $allowedUnits[$unit],
            'ingredients' => $parsedIngredientsList ?? [],
            'isQuick'     => ((int)$cookTimeMin <= 20),
            'submittedAt' => date('H:i:s, d.m.Y'),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична робота №2 — Варіант 6 (Кулінарна книга)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-container">
        <!-- Шапка -->
        <header class="header">
            <div class="header-badge">Практична робота №2 • Варіант 6</div>
            <h1>🍳 Форма додавання рецепта</h1>
            <p class="subtitle">
                Взаємодія з користувачем: серверна та клієнтська валідація, захист від XSS, обробка помилок та збереження стану в <code>localStorage</code>.
            </p>
        </header>

        <!-- Інтерактивна інформаційна панель localStorage -->
        <section class="storage-banner">
            <div class="storage-info">
                <span class="storage-icon">💾</span>
                <div>
                    <strong>Сценарій localStorage (Варіант 6):</strong>
                    <span>Запам'ятовування обраної одиниці виміру (грами / унції) для інгредієнтів.</span>
                </div>
            </div>
            <div class="storage-status">
                Збережено в браузері:
                <code id="storagePreview">немає запису</code>
                <button type="button" id="clearStorageBtn" class="btn-sm">Скинути налаштування</button>
            </div>
        </section>

        <!-- Крок 4: Показ результату обробки -->
        <?php if ($isSuccess): ?>
            <!-- Блок успіху з прийнятими даними -->
            <section class="result-card success-card">
                <div class="result-header">
                    <span class="status-icon">🎉</span>
                    <div>
                        <h2>Дані успішно валідовано та прийнято на сервері!</h2>
                        <p class="timestamp">Час обробки: <?= htmlspecialchars($submittedData['submittedAt']) ?></p>
                    </div>
                </div>

                <div class="accepted-data-grid">
                    <div class="data-item">
                        <span class="data-label">Назва рецепта:</span>
                        <span class="data-value">«<?= htmlspecialchars($submittedData['title']) ?>»</span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Час приготування:</span>
                        <span class="data-value">
                            <?= $submittedData['cookTimeMin'] ?> хв
                            <?php if ($submittedData['isQuick']): ?>
                                <span class="badge badge-quick">⚡ Швидкий рецепт (≤ 20 хв)</span>
                            <?php else: ?>
                                <span class="badge badge-regular">⏱ Звичайний час</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="data-item">
                        <span class="data-label">Одиниця виміру (localStorage):</span>
                        <span class="data-value highlight-unit">
                            <?= htmlspecialchars($submittedData['unitLabel']) ?>
                        </span>
                    </div>

                    <div class="data-item full-width">
                        <span class="data-label">Прийняті інгредієнти (<?= count($submittedData['ingredients']) ?> поз.):</span>
                        <ul class="ingredients-list">
                            <?php foreach ($submittedData['ingredients'] as $ing): ?>
                                <li><?= htmlspecialchars($ing) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="result-actions">
                    <a href="form.php" class="btn-primary">➕ Додати ще один рецепт</a>
                </div>
            </section>
        <?php endif; ?>

        <!-- Блок загальних помилок валідації (якщо вони є) -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <div class="alert-icon">⚠️</div>
                <div>
                    <strong>Помилка валідації форми на сервері!</strong>
                    <p>Будь ласка, виправте виявлені невідповідності та надішліть форму повторно:</p>
                    <ul class="error-summary-list">
                        <?php foreach ($errors as $field => $msg): ?>
                            <li><strong><?= htmlspecialchars($field) ?>:</strong> <?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Блок клієнтських помилок (заповнюється через JS) -->
        <div id="jsErrorAlert" class="alert alert-warning" style="display: none;">
            <div class="alert-icon">⚡</div>
            <div>
                <strong>Клієнтська валідація (JavaScript):</strong>
                <span id="jsErrorMessage"></span>
            </div>
        </div>

        <!-- Крок 2: HTML-форма -->
        <section class="form-section <?= $isSuccess ? 'form-collapsed' : '' ?>">
            <form id="recipeForm" method="post" action="form.php" novalidate>
                <div class="form-card">
                    <h2 class="form-title">Параметри нового рецепта</h2>

                    <!-- Поле 1: Назва рецепта (title) -->
                    <div class="form-group <?= isset($errors['title']) ? 'has-error' : '' ?>">
                        <label for="title" class="form-label">
                            Назва рецепта <span class="required-star">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            placeholder="Наприклад: Гарбузовий крем-суп з вершками"
                            value="<?= htmlspecialchars($title) ?>"
                            required
                            minlength="3"
                            maxlength="100"
                        >
                        <small class="field-hint">Від 3 до 100 символів</small>
                        <div class="error-message" id="titleError">
                            <?= htmlspecialchars($errors['title'] ?? '') ?>
                        </div>
                    </div>

                    <!-- Рядок: Час приготування + Одиниця виміру (сценарій localStorage) -->
                    <div class="form-row">
                        <!-- Поле 2: Час приготування (cookTimeMin) -->
                        <div class="form-group <?= isset($errors['cookTimeMin']) ? 'has-error' : '' ?>">
                            <label for="cookTimeMin" class="form-label">
                                Час приготування (хв) <span class="required-star">*</span>
                            </label>
                            <input
                                type="number"
                                id="cookTimeMin"
                                name="cookTimeMin"
                                class="form-control"
                                placeholder="30"
                                value="<?= htmlspecialchars((string)$cookTimeMin) ?>"
                                required
                                min="1"
                                max="1440"
                                step="1"
                            >
                            <small class="field-hint">Ціле число більше 0 (≤ 20 хв — швидкий рецепт)</small>
                            <div class="error-message" id="cookTimeError">
                                <?= htmlspecialchars($errors['cookTimeMin'] ?? '') ?>
                            </div>
                        </div>

                        <!-- Поле 3: Одиниця виміру (localStorage) -->
                        <div class="form-group <?= isset($errors['unit']) ? 'has-error' : '' ?>">
                            <label class="form-label">
                                Бажана міра інгредієнтів <span class="storage-tag">localStorage</span>
                            </label>
                            <div class="radio-toggle-group">
                                <?php foreach ($allowedUnits as $uKey => $uLabel): ?>
                                    <label class="radio-pill">
                                        <input
                                            type="radio"
                                            name="unit"
                                            value="<?= htmlspecialchars($uKey) ?>"
                                            <?= $unit === $uKey ? 'checked' : '' ?>
                                        >
                                        <span class="pill-label"><?= htmlspecialchars($uLabel) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="field-hint">Вибір зберігається в браузері автоматично</small>
                            <div class="error-message" id="unitError">
                                <?= htmlspecialchars($errors['unit'] ?? '') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Поле 4: Перелік інгредієнтів (ingredients) -->
                    <div class="form-group <?= isset($errors['ingredients']) ? 'has-error' : '' ?>">
                        <div class="label-with-badge">
                            <label for="ingredients" class="form-label">
                                Перелік інгредієнтів <span class="required-star">*</span>
                            </label>
                            <span class="unit-indicator" id="activeUnitIndicator">
                                Поточна міра: <strong>Грами (г)</strong>
                            </span>
                        </div>
                        <textarea
                            id="ingredients"
                            name="ingredients"
                            rows="5"
                            class="form-control textarea-control"
                            placeholder="Введіть кожен інгредієнт з нового рядка або через кому.&#10;Наприклад:&#10;Гарбуз - 500 г&#10;Вершки 20% - 150 мл&#10;Цибуля порей - 1 шт&#10;Мускатний горіх - 2 г"
                            required
                        ><?= htmlspecialchars($ingredients) ?></textarea>
                        <small class="field-hint">Мінімум один інгредієнт (кожен з нового рядка або через кому)</small>
                        <div class="error-message" id="ingredientsError">
                            <?= htmlspecialchars($errors['ingredients'] ?? '') ?>
                        </div>
                    </div>

                    <!-- Кнопки керування -->
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary" id="submitBtn">
                            💾 Зберегти рецепт
                        </button>
                        <button type="button" class="btn-secondary" id="resetBtn">
                            🔄 Очистити форму
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Підвал -->
        <footer class="footer">
            <p>Практична робота №2 • Спеціальність 121 «Інженерія програмного забезпечення» • ФІОТ КПІ</p>
            <p class="footer-sub">Варіант 6 (Кулінарна книга) • PHP <?= PHP_VERSION ?> • Обробка форми та localStorage</p>
        </footer>
    </div>

    <!-- Підключення клієнтського JavaScript (Кроки 5 і 6) -->
    <script src="script.js"></script>
</body>
</html>
