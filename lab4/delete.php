<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Отримуємо id або з POST, або з GET
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if ($id && $id > 0) {
    try {
        // Видалення через підготовлений запит (DELETE FROM recipes WHERE id = :id)
        deleteRecipe($pdo, (int)$id);
        header('Location: index.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?msg=error&detail=' . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: index.php?msg=not_found');
exit;
