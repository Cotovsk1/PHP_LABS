<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не підтримується'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$title         = $input['title'] ?? '';
$ingredients   = $input['ingredients'] ?? '';
$cook_time_min = $input['cook_time_min'] ?? 0;

$errors = [];
if (empty($title))       $errors['title'] = 'Назва обов\'язкова.';
if (empty($ingredients)) $errors['ingredients'] = 'Інгредієнти обов\'язкові.';
if (empty($cook_time_min) || !is_numeric($cook_time_min) || $cook_time_min <= 0) {
    $errors['cook_time_min'] = 'Час має бути > 0.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Помилки валідації', 'details' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO recipes (title, ingredients, cook_time_min) VALUES (:t, :i, :c)");
    $stmt->execute(['t' => $title, 'i' => $ingredients, 'c' => $cook_time_min]);
    http_response_code(201);
    echo json_encode(['id' => $pdo->lastInsertId(), 'title' => $title, 'ingredients' => $ingredients, 'cook_time_min' => $cook_time_min], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Помилка INSERT: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}