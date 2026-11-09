<?php
declare(strict_types=1);

/**
 * Практична робота №6: Розробка власного API
 * Варіант №6: Кулінарна книга
 *
 * Крок 1-2. Єдина точка входу, заголовки, маршрутизація.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

// Функція для стандартизованої відповіді
function sendResponse(bool $success, $data_or_error = null, int $status = 200) {
    http_response_code($status);
    $response = ['success' => $success];
    if ($success) {
        $response['data'] = $data_or_error;
    } else {
        $response['error'] = $data_or_error;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Перевірка ресурсу
if ($resource !== 'recipes') {
    sendResponse(false, 'Невідомий ресурс. Доступні ресурси: recipes', 404);
}

// МАРШРУТИЗАЦІЯ (Крок 3-7)
switch ($method) {
    case 'GET':
        handleGet($pdo, $id, $action);
        break;
    case 'POST':
        handlePost($pdo, $action);
        break;
    default:
        sendResponse(false, "Метод $method не підтримується для цього ресурсу", 405);
        break;
}

/**
 * Крок 4. Реалізація GET (Список, Одиничний запис, Пошук)
 */
function handleGet(PDO $pdo, ?int $id, ?string $action) {
    // 1. Отримати одиничний запис за ID
    if ($id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
        $stmt->execute([$id]);
        $recipe = $stmt->fetch();
        if ($recipe) {
            sendResponse(true, $recipe);
        } else {
            sendResponse(false, "Рецепт з ID $id не знайдено", 404);
        }
    }

    // 2. Список усіх рецептів
    $stmt = $pdo->query("SELECT * FROM recipes ORDER BY id DESC");
    $recipes = $stmt->fetchAll();
    sendResponse(true, $recipes);
}

/**
 * Крок 5-6. Реалізація POST (Створення, Доменна дія search)
 */
function handlePost(PDO $pdo, ?string $action) {
    // Отримуємо дані з тіла запиту (JSON або Form Data)
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Крок 6. Доменна дія: action=search (пошук за часом приготування)
    if ($action === 'search') {
        $max_time = $input['max_time'] ?? $_GET['max_time'] ?? null;
        if ($max_time === null || !is_numeric($max_time)) {
            sendResponse(false, "Параметр 'max_time' є обов'язковим для пошуку та має бути числом", 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM recipes WHERE cook_time_min <= ? ORDER BY cook_time_min ASC");
        $stmt->execute([(int)$max_time]);
        $found = $stmt->fetchAll();
        sendResponse(true, $found);
    }

    // Крок 5. Створення нового запису (якщо немає action)
    if ($action === null) {
        $title = trim((string)($input['title'] ?? ''));
        $ingredients = trim((string)($input['ingredients'] ?? ''));
        $cook_time_min = $input['cook_time_min'] ?? null;

        // Перевірка обов'язкових полів (Крок 7)
        if ($title === '' || $ingredients === '' || $cook_time_min === null) {
            sendResponse(false, "Відсутні обов'язкові поля: title, ingredients, cook_time_min", 400);
        }

        if (!is_numeric($cook_time_min) || (int)$cook_time_min <= 0) {
            sendResponse(false, "Поле 'cook_time_min' має бути числом більше 0", 400);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO recipes (title, ingredients, cook_time_min) VALUES (?, ?, ?)");
            $stmt->execute([$title, $ingredients, (int)$cook_time_min]);
            $newId = $pdo->lastInsertId();

            sendResponse(true, [
                'id' => $newId,
                'title' => $title,
                'ingredients' => $ingredients,
                'cook_time_min' => (int)$cook_time_min
            ], 201);
        } catch (PDOException $e) {
            sendResponse(false, "Помилка бази даних: " . $e->getMessage(), 500);
        }
    }

    sendResponse(false, "Невідома дія (action). Доступні дії для POST: search або порожня дія для створення.", 400);
}
