<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    header('Location: index.php?msg=not_found');
    exit;
}

// Завантаження поточного запису з БД через підготовлений запит (SELECT ... WHERE id = :id)
$recipe = getRecipeById($pdo, (int)$id);
if (!$recipe) {
    header('Location: index.php?msg=not_found');
    exit;
}

$errors = [];
$title = $recipe['title'];
$ingredients = $recipe['ingredients'];
$cookTimeMin = (string)$recipe['cook_time_min'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $cookTimeRaw = trim($_POST['cook_time_min'] ?? '');

    // Валідація
    if ($title === '') {
        $errors['title'] = "Назва рецепта не може бути порожньою.";
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = "Назва не повинна перевищувати 255 символів.";
    }

    if ($ingredients === '') {
        $errors['ingredients'] = "Вкажіть перелік інгредієнтів.";
    }

    if ($cookTimeRaw === '' || !is_numeric($cookTimeRaw) || (int)$cookTimeRaw <= 0) {
        $errors['cook_time_min'] = "Час приготування має бути додатним числом хвилин.";
    } else {
        $cookTimeMin = $cookTimeRaw;
    }

    if (empty($errors)) {
        try {
            // Крок 5: UPDATE через підготовлений запит з умовою WHERE id = :id
            updateRecipe($pdo, (int)$id, $title, $ingredients, (int)$cookTimeMin);
            header('Location: index.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $errors['db'] = "Помилка оновлення запису: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування рецепта #<?= (int)$id ?> — Лабораторна №4</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container form-page-container">
        <!-- Шапка форми -->
        <header class="header mini-header">
            <div class="header-content">
                <span class="header-icon">✏️</span>
                <div>
                    <span class="header-badge">Крок 5 · UPDATE</span>
                    <h1>Редагування рецепта #<?= (int)$id ?></h1>
                    <p class="subtitle">Зміна даних у БД через підготовлений запит <code>UPDATE recipes SET ... WHERE id = :id</code>.</p>
                </div>
            </div>
        </header>

        <main class="card">
            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($errors['db'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="edit.php" method="POST" class="recipe-form" novalidate>
                <input type="hidden" name="id" value="<?= (int)$id ?>">

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
                        required
                    ><?= htmlspecialchars($ingredients, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php if (isset($errors['ingredients'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['ingredients'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
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
                        min="1" 
                        max="1440" 
                        value="<?= htmlspecialchars((string)$cookTimeMin, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <?php if (isset($errors['cook_time_min'])): ?>
                        <div class="error-text"><?= htmlspecialchars($errors['cook_time_min'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <!-- Кнопки керування -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Зберегти зміни (UPDATE)
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
</body>
</html>
