<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$errors = [];
$title = '';
$ingredients = '';
$cookTimeMin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $cookTimeRaw = trim($_POST['cook_time_min'] ?? '');

    // Валідація даних
    if ($title === '') {
        $errors['title'] = "Назва рецепта не може бути порожньою.";
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = "Назва не повинна перевищувати 255 символів.";
    }

    if ($ingredients === '') {
        $errors['ingredients'] = "Вкажіть перелік інгредієнтів.";
    }

    if ($cookTimeRaw === '' || !is_numeric($cookTimeRaw) || (int)$cookTimeRaw <= 0) {
        $errors['cook_time_min'] = "Час приготування має бути додатним цілим числом хвилин.";
    } else {
        $cookTimeMin = (int)$cookTimeRaw;
    }

    // Якщо помилок немає — виконуємо INSERT через безпечний підготовлений запит
    if (empty($errors)) {
        try {
            addRecipe($pdo, $title, $ingredients, (int)$cookTimeMin);
            header('Location: index.php?msg=added');
            exit;
        } catch (PDOException $e) {
            $errors['db'] = "Помилка бази даних: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати новий рецепт — Лабораторна №4</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container form-page-container">
        <!-- Шапка форми -->
        <header class="header mini-header">
            <div class="header-content">
                <span class="header-icon">🍳</span>
                <div>
                    <span class="header-badge">Крок 4 · INSERT</span>
                    <h1>Додавання нового рецепта</h1>
                    <p class="subtitle">Внесення даних до таблиці <code>recipes</code> через безпечний підготовлений запит (Prepared Statement).</p>
                </div>
            </div>
        </header>

        <main class="card">
            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($errors['db'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="add.php" method="POST" class="recipe-form" novalidate>
                <!-- Назва рецепта -->
                <div class="form-group <?= isset($errors['title']) ? 'has-error' : '' ?>">
                    <label for="title" class="form-label">
                        Назва рецепта <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        placeholder="Наприклад: Гарбузовий суп-пюре з вершками" 
                        value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <!-- Інгредієнти -->
                <div class="form-group <?= isset($errors['ingredients']) ? 'has-error' : '' ?>">
                    <label for="ingredients" class="form-label">
                        Інгредієнти <span class="required">*</span>
                    </label>
                    <textarea 
                        id="ingredients" 
                        name="ingredients" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Перелічіть інгредієнти через кому (наприклад: Гарбуз, вершки 20%, вершкове масло, мускатний горіх)"
                        required
                    ><?= htmlspecialchars($ingredients, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php if (isset($errors['ingredients'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['ingredients'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <span class="help-text">Можна ввести будь-які спецсимволи чи лапки — підготовлений запит повністю захищає від SQL-ін'єкцій.</span>
                </div>

                <!-- Час приготування -->
                <div class="form-group <?= isset($errors['cook_time_min']) ? 'has-error' : '' ?>">
                    <label for="cook_time_min" class="form-label">
                        Час приготування (хв) <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="cook_time_min" 
                        name="cook_time_min" 
                        class="form-control" 
                        placeholder="30" 
                        min="1" 
                        max="1440" 
                        value="<?= htmlspecialchars((string)$cookTimeMin, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <?php if (isset($errors['cook_time_min'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['cook_time_min'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <span class="help-text">Якщо час ≤ 20 хв, рецепт автоматично отримає мітку «⚡ Швидкий рецепт».</span>
                </div>

                <!-- Кнопка для швидкого тесту SQL-ін'єкції (Крок 7) -->
                <div class="sql-injection-tester">
                    <span class="tester-label">🧪 Тест на SQL-ін'єкцію (Крок 7):</span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="fillSqlInjectionTest()">
                        Вставити рядок із лапками та <code>' OR '1'='1</code>
                    </button>
                </div>

                <!-- Кнопки керування -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Зберегти в базу даних
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        ← Скасувати і повернутися
                    </a>
                </div>
            </form>
        </main>

        <footer class="footer">
            Практична робота №4 · Кафедра ІПЗ ФІОТ КПІ · <strong>Варіант №6: Кулінарна книга</strong>
        </footer>
    </div>

    <script>
    function fillSqlInjectionTest() {
        document.getElementById('title').value = "O'Connor's Special Dessert'); DROP TABLE test; --";
        document.getElementById('ingredients').value = "Шоколад ' OR '1'='1, вершки \" OR \"\"=\"\", ваніль, горіхи";
        document.getElementById('cook_time_min').value = "15";
    }
    </script>
</body>
</html>
