<?php
declare(strict_types=1);

/**
 * Практична робота №4: Функції для роботи з базою даних рецептів (Варіант 6)
 * Усі операції виконуються виключно через підготовлені запити (prepared statements).
 */

/**
 * Отримати всі рецепти (SELECT * FROM recipes)
 * 
 * @param PDO $pdo
 * @return array
 */
function getAllRecipes(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM recipes ORDER BY id DESC');
    return $stmt->fetchAll();
}

/**
 * Отримати один рецепт за його первинним ключем (id)
 * 
 * @param PDO $pdo
 * @param int $id
 * @return array|null
 */
function getRecipeById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $recipe = $stmt->fetch();
    return $recipe ?: null;
}

/**
 * Вибірка за інгредієнтом (Вимога варіанта 6):
 * findByIngredient($text) - SELECT * FROM recipes WHERE ingredients LIKE :pattern
 * 
 * @param PDO $pdo
 * @param string $text
 * @return array
 */
function findByIngredient(PDO $pdo, string $text): array
{
    $stmt = $pdo->prepare('SELECT * FROM recipes WHERE ingredients LIKE :pattern ORDER BY id DESC');
    // Використання плейсхолдера унеможливлює SQL-ін'єкцію через рядок пошуку
    $stmt->execute([':pattern' => '%' . $text . '%']);
    return $stmt->fetchAll();
}

/**
 * Вибірка найшвидшого рецепта (Вимога варіанта 6):
 * shortestCookTime() - SELECT * FROM recipes ORDER BY cook_time_min ASC LIMIT 1
 * 
 * @param PDO $pdo
 * @return array|null
 */
function shortestCookTime(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM recipes ORDER BY cook_time_min ASC LIMIT 1');
    $recipe = $stmt->fetch();
    return $recipe ?: null;
}

/**
 * Додавання рецепта (Вимога варіанта 6):
 * addRecipe() - INSERT
 * 
 * @param PDO $pdo
 * @param string $title
 * @param string $ingredients
 * @param int $cookTimeMin
 * @return int ID новоствореного рядка
 */
function addRecipe(PDO $pdo, string $title, string $ingredients, int $cookTimeMin): int
{
    $sql = 'INSERT INTO recipes (title, ingredients, cook_time_min) VALUES (:title, :ingredients, :cook_time_min)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title'         => $title,
        ':ingredients'   => $ingredients,
        ':cook_time_min' => $cookTimeMin,
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Оновлення рецепта (Вимога варіанта 6):
 * updateRecipe($id, ...) - UPDATE ... WHERE id = :id
 * 
 * @param PDO $pdo
 * @param int $id
 * @param string $title
 * @param string $ingredients
 * @param int $cookTimeMin
 * @return bool
 */
function updateRecipe(PDO $pdo, int $id, string $title, string $ingredients, int $cookTimeMin): bool
{
    $sql = 'UPDATE recipes SET title = :title, ingredients = :ingredients, cook_time_min = :cook_time_min WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':id'            => $id,
        ':title'         => $title,
        ':ingredients'   => $ingredients,
        ':cook_time_min' => $cookTimeMin,
    ]);
}

/**
 * Видалення рецепта (Вимога варіанта 6):
 * deleteRecipe($id) - DELETE FROM recipes WHERE id = :id
 * 
 * @param PDO $pdo
 * @param int $id
 * @return bool
 */
function deleteRecipe(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

/**
 * Допоміжна функція: повертає бейдж часу відповідно до правил попередніх робіт (<= 20 хв)
 * 
 * @param int $cookTimeMin
 * @return array
 */
function getRecipeBadge(int $cookTimeMin): array
{
    if ($cookTimeMin <= 20) {
        return [
            'label' => '⚡ Швидкий рецепт',
            'class' => 'badge-quick'
        ];
    }
    return [
        'label' => '⏱️ Звичайний час',
        'class' => 'badge-regular'
    ];
}
