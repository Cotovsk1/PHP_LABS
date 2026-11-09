<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$query = trim((string)($_GET['q'] ?? ''));
$sql   = "SELECT * FROM recipes";
$params = [];

if ($query !== '') {
    $sql .= " WHERE ingredients LIKE :query_ing OR title LIKE :query_title";
    $params['query_ing'] = '%' . $query . '%';
    $params['query_title'] = '%' . $query . '%';
}
$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll();
    echo json_encode($recipes, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Помилка виконання запиту: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
