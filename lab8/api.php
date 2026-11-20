<?php
declare(strict_types=1);

/**
 * Практична робота №8: Захищений REST API (SQLi, XSS, CSRF, контроль доступу IDOR, обробка помилок).
 */

$requestStartTime = microtime(true);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/lib/Security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/Cache.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/classes/Cookbook.php';

Security::initSession();

$cache    = new FileCache('cookbook_lab8_');
$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? null;
$id       = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$action   = $_GET['action'] ?? null;

function sendApiResponse(bool $success, mixed $payload, int $status = 200, array $meta = []): never
{
    http_response_code($status);
    $body = ['success' => $success];
    if ($success) {
        $body['data'] = $payload;
        if ($meta !== []) {
            $body['meta'] = $meta;
        }
    } else {
        $body['error'] = $payload;
        if ($meta !== []) {
            $body['details'] = $meta;
        }
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function buildProfilerMeta(TrackedPDO $pdo, FileCache $cache, float $startTime, bool $cacheHit): array
{
    return [
        'query_count'        => $pdo->getQueryCount(),
        'sql_time_ms'        => $pdo->getTotalSqlTimeMs(),
        'execution_time_ms'  => round((microtime(true) - $startTime) * 1000, 2),
        'peak_memory_mb'     => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'cache_hit'          => $cacheHit,
        'active_cache_files' => $cache->getActiveFilesCount(45),
        'csrf_protected'     => true,
    ];
}

if ($resource !== 'recipes') {
    sendApiResponse(false, 'Невідомий ресурс. Доступний ресурс: recipes', 404);
}

try {
    // Заборона будь-яких дій зміни/видалення через GET
    if ($method === 'GET') {
        if (in_array($action, ['delete', 'update', 'create', 'clear_cache'], true)) {
            sendApiResponse(false, 'Дії, що змінюють дані, заборонено виконувати методом GET. Використовуйте POST із CSRF-токеном.', 405);
        }
        handleGet($pdo, $cache, $id, $action, $requestStartTime);
    }

    if ($method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $jsonBody = ($rawBody !== false && $rawBody !== '') ? json_decode($rawBody, true) : null;
        $input = is_array($jsonBody) ? $jsonBody : $_POST;

        // Обов'язкова перевірка CSRF-токена для всіх мутуючих POST-операцій
        if ($action !== 'search') {
            $tokenFromInput = is_string($input['csrf_token'] ?? null) ? $input['csrf_token'] : null;
            if (!Security::verifyCsrfToken($tokenFromInput)) {
                sendApiResponse(false, 'Недійсний або відсутній CSRF-токен (403 Forbidden). Оновіть сторінку та повторіть спробу.', 403);
            }
        }

        handlePost($pdo, $cache, $action, $input, $requestStartTime);
    }

    sendApiResponse(false, "HTTP-метод {$method} не підтримується.", 405);
} catch (PDOException $e) {
    error_log('[SecurityAudit] API PDOException: ' . $e->getMessage());
    sendApiResponse(false, 'Внутрішня помилка обробки запиту. Спробуйте пізніше.', 500);
} catch (Throwable $e) {
    error_log('[SecurityAudit] API Error: ' . $e->getMessage());
    sendApiResponse(false, 'Непередбачена помилка сервера.', 500);
}

function enrichRecipeRow(array $row): array
{
    $item = Cookbook::createFromDbRow($row)->toArray();
    $item['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
    $item['author_username'] = (string) ($row['author_username'] ?? 'Гість');
    return $item;
}

/**
 * GET /api.php?resource=recipes
 */
function handleGet(TrackedPDO $pdo, FileCache $cache, ?int $id, ?string $action, float $startTime): never
{
    if ($id !== null) {
        $stmt = $pdo->prepare(
            'SELECT r.*, u.username AS author_username
             FROM recipes r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            sendApiResponse(false, "Рецепт з ID {$id} не знайдено.", 404);
        }
        sendApiResponse(true, enrichRecipeRow($row), 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    $maxTimeRaw = trim((string) ($_GET['max_time'] ?? ''));
    $maxTime = ($maxTimeRaw !== '' && is_numeric($maxTimeRaw) && (int) $maxTimeRaw > 0) ? (int) $maxTimeRaw : null;

    if ($action === 'shortest') {
        $cached = $cache->get('shortest_top3', 45);
        if (is_array($cached)) {
            sendApiResponse(true, $cached, 200, buildProfilerMeta($pdo, $cache, $startTime, true));
        }
        $stmt = $pdo->prepare(
            'SELECT r.*, u.username AS author_username
             FROM recipes r
             LEFT JOIN users u ON r.user_id = u.id
             ORDER BY r.cook_time_min ASC LIMIT 3'
        );
        $stmt->execute();
        $list = array_map('enrichRecipeRow', $stmt->fetchAll());
        $cache->set('shortest_top3', $list);
        sendApiResponse(true, $list, 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    $cacheKey = 'list_' . md5("{$q}|{$maxTime}");
    $cachedPayload = $cache->get($cacheKey, 45);
    if (is_array($cachedPayload)) {
        sendApiResponse(true, $cachedPayload, 200, buildProfilerMeta($pdo, $cache, $startTime, true));
    }

    $conditions = [];
    $params = [];

    if ($q !== '') {
        $safeLike = '%' . Security::escapeLikeWildcards($q) . '%';
        $conditions[] = "(r.ingredients LIKE :q_ing ESCAPE '\\\\' OR r.title LIKE :q_title ESCAPE '\\\\')";
        $params[':q_ing'] = $safeLike;
        $params[':q_title'] = $safeLike;
    }

    if ($maxTime !== null) {
        $conditions[] = 'r.cook_time_min <= :max_time';
        $params[':max_time'] = $maxTime;
    }

    $sql = 'SELECT r.*, u.username AS author_username
            FROM recipes r
            LEFT JOIN users u ON r.user_id = u.id';
    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY r.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $cookbook = new Cookbook();
    $ingredientFreq = [];
    $parsed = [];

    foreach ($rows as $row) {
        $obj = Cookbook::createFromDbRow($row);
        $cookbook->addRecipe($obj);
        $names = array_keys($obj->getIngredients());
        $primary = $names[0] ?? '';
        $norm = mb_strtolower(trim($primary));
        if ($norm !== '') {
            $ingredientFreq[$norm] = ($ingredientFreq[$norm] ?? 0) + 1;
        }
        $parsed[] = [
            'row'     => $row,
            'primary' => $primary,
            'norm'    => $norm,
        ];
    }

    $enriched = [];
    foreach ($parsed as $entry) {
        $item = enrichRecipeRow($entry['row']);
        $item['primary_ingredient'] = $entry['primary'];
        $item['similar_recipes_count'] = $entry['norm'] !== '' ? max(0, ($ingredientFreq[$entry['norm']] ?? 1) - 1) : 0;
        $enriched[] = $item;
    }

    $fastest = $cookbook->shortestCookTime();
    $payload = [
        'recipes' => $enriched,
        'stats'   => [
            'total_count'       => $cookbook->count(),
            'average_cook_time' => $cookbook->getAverageCookTime(),
            'quick_count'       => $cookbook->getQuickRecipesCount(),
            'fastest_recipe'    => $fastest ? $fastest->toArray() : null,
        ],
    ];

    $cache->set($cacheKey, $payload);
    sendApiResponse(true, $payload, 200, buildProfilerMeta($pdo, $cache, $startTime, false));
}

/**
 * Перевіряє, чи має поточний користувач право змінювати або видаляти запис (захист від IDOR).
 */
function assertCanModifyRecipe(TrackedPDO $pdo, int $recipeId): array
{
    $currentUser = Security::currentUser();
    if (!$currentUser) {
        sendApiResponse(false, 'Для цієї дії необхідно увійти в систему (401 Unauthorized).', 401);
    }

    $stmt = $pdo->prepare('SELECT id, user_id FROM recipes WHERE id = :id');
    $stmt->execute([':id' => $recipeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        sendApiResponse(false, "Рецепт з ID {$recipeId} не знайдено.", 404);
    }

    $ownerId = $existing['user_id'] !== null ? (int) $existing['user_id'] : null;
    $isOwner = $ownerId !== null && $ownerId === (int) $currentUser['id'];
    $isAdmin = ($currentUser['role'] ?? '') === 'admin';

    if (!$isOwner && !$isAdmin) {
        sendApiResponse(false, 'Доступ заборонено (403 Forbidden): ви можете змінювати або видаляти лише власні рецепти.', 403);
    }

    return $currentUser;
}

/**
 * POST /api.php?resource=recipes
 */
function handlePost(TrackedPDO $pdo, FileCache $cache, ?string $action, array $input, float $startTime): never
{
    if ($action === 'clear_cache') {
        $removed = $cache->clearAll();
        sendApiResponse(true, ['cleared_files' => $removed], 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    if ($action === 'search') {
        $maxTime = $input['max_time'] ?? null;
        if ($maxTime === null || filter_var($maxTime, FILTER_VALIDATE_INT) === false || (int) $maxTime <= 0) {
            sendApiResponse(false, "Параметр 'max_time' має бути цілим числом більше 0.", 400);
        }
        $stmt = $pdo->prepare(
            'SELECT r.*, u.username AS author_username
             FROM recipes r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.cook_time_min <= :max_time
             ORDER BY r.cook_time_min ASC'
        );
        $stmt->execute([':max_time' => (int) $maxTime]);
        $list = array_map('enrichRecipeRow', $stmt->fetchAll());
        sendApiResponse(true, $list, 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    if ($action === 'update') {
        $id = isset($input['id']) && filter_var($input['id'], FILTER_VALIDATE_INT) !== false ? (int) $input['id'] : null;
        if ($id === null || $id <= 0) {
            sendApiResponse(false, 'Не вказано коректний ID рецепта для оновлення.', 400);
        }

        // Перевірка авторизації та належності запису (IDOR)
        $currentUser = assertCanModifyRecipe($pdo, $id);

        $validated = validateRecipeInput($input);
        if ($validated['errors'] !== []) {
            sendApiResponse(false, 'Помилка серверної валідації при оновленні.', 400, $validated['errors']);
        }

        $d = $validated['data'];
        $stmt = $pdo->prepare(
            'UPDATE recipes
             SET title = :title, ingredients = :ingredients, steps = :steps, cook_time_min = :cook_time_min,
                 servings = :servings, difficulty = :difficulty, is_vegan = :is_vegan, substitutions = :substitutions
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'            => $id,
            ':title'         => $d['title'],
            ':ingredients'   => $d['ingredients'],
            ':steps'         => $d['steps'],
            ':cook_time_min' => $d['cook_time_min'],
            ':servings'      => $d['servings'],
            ':difficulty'    => $d['difficulty'],
            ':is_vegan'      => $d['is_vegan'],
            ':substitutions' => $d['substitutions'],
        ]);

        $cache->clearAll();
        $d['id'] = $id;
        $d['user_id'] = $currentUser['id'];
        $d['author_username'] = $currentUser['username'];
        sendApiResponse(true, enrichRecipeRow($d), 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    if ($action === 'delete') {
        $id = isset($input['id']) && filter_var($input['id'], FILTER_VALIDATE_INT) !== false ? (int) $input['id'] : null;
        if ($id === null || $id <= 0) {
            sendApiResponse(false, 'Не вказано коректний ID рецепта для видалення.', 400);
        }

        // Перевірка авторизації та належності запису (IDOR)
        assertCanModifyRecipe($pdo, $id);

        $stmt = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $cache->clearAll();
        sendApiResponse(true, ['deleted_id' => $id], 200, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    if ($action === null || $action === '') {
        $currentUser = Security::currentUser();
        if (!$currentUser) {
            sendApiResponse(false, 'Щоб додати новий рецепт, увійдіть у свій обліковий запис (401 Unauthorized).', 401);
        }

        $validated = validateRecipeInput($input);
        if ($validated['errors'] !== []) {
            sendApiResponse(false, 'Помилка серверної валідації форми.', 400, $validated['errors']);
        }

        $d = $validated['data'];
        $userId = (int) $currentUser['id'];

        $stmt = $pdo->prepare(
            'INSERT INTO recipes (user_id, title, ingredients, steps, cook_time_min, servings, difficulty, is_vegan, substitutions)
             VALUES (:user_id, :title, :ingredients, :steps, :cook_time_min, :servings, :difficulty, :is_vegan, :substitutions)'
        );
        $stmt->execute([
            ':user_id'       => $userId,
            ':title'         => $d['title'],
            ':ingredients'   => $d['ingredients'],
            ':steps'         => $d['steps'],
            ':cook_time_min' => $d['cook_time_min'],
            ':servings'      => $d['servings'],
            ':difficulty'    => $d['difficulty'],
            ':is_vegan'      => $d['is_vegan'],
            ':substitutions' => $d['substitutions'],
        ]);

        $d['id'] = (int) $pdo->lastInsertId();
        $d['user_id'] = $userId;
        $d['author_username'] = $currentUser['username'];
        $cache->clearAll();
        sendApiResponse(true, enrichRecipeRow($d), 201, buildProfilerMeta($pdo, $cache, $startTime, false));
    }

    sendApiResponse(false, 'Невідома дія action.', 400);
}

/**
 * Серверна валідація полів рецепта.
 */
function validateRecipeInput(array $input): array
{
    $errors = [];

    $title         = trim((string) ($input['title'] ?? ''));
    $ingredients   = trim((string) ($input['ingredients'] ?? ''));
    $steps         = trim((string) ($input['steps'] ?? ''));
    $cookTimeRaw   = $input['cook_time_min'] ?? null;
    $servingsRaw   = $input['servings'] ?? 2;
    $difficulty    = trim((string) ($input['difficulty'] ?? 'Середній'));
    $isVegan       = !empty($input['is_vegan']) && (string) $input['is_vegan'] !== '0' ? 1 : 0;
    $substitutions = trim((string) ($input['substitutions'] ?? ''));

    if ($title === '' || mb_strlen($title) < 3 || mb_strlen($title) > 255) {
        $errors['title'] = 'Назва рецепта обов\'язкова (від 3 до 255 символів).';
    }

    if ($ingredients === '' || parseIngredientsString($ingredients) === []) {
        $errors['ingredients'] = 'Перелік інгредієнтів обов\'язковий і має містити хоча б один пункт.';
    }

    if ($steps === '' || mb_strlen($steps) < 5) {
        $errors['steps'] = 'Опис кроків приготування обов\'язковий (мінімум 5 символів).';
    }

    if ($cookTimeRaw === null || $cookTimeRaw === '' || filter_var($cookTimeRaw, FILTER_VALIDATE_INT) === false) {
        $errors['cook_time_min'] = 'Час приготування має бути цілим додатним числом.';
    } elseif ((int) $cookTimeRaw <= 0 || (int) $cookTimeRaw > 1440) {
        $errors['cook_time_min'] = 'Час приготування повинен бути в межах 1–1440 хв.';
    }

    $servings = filter_var($servingsRaw, FILTER_VALIDATE_INT);
    if ($servings === false || $servings < 1 || $servings > 100) {
        $servings = 2;
    }

    if (!in_array($difficulty, ['Легкий', 'Середній', 'Складний'], true)) {
        $difficulty = 'Середній';
    }

    return [
        'data' => [
            'title'         => $title,
            'ingredients'   => $ingredients,
            'steps'         => $steps,
            'cook_time_min' => (int) $cookTimeRaw,
            'servings'      => (int) $servings,
            'difficulty'    => $difficulty,
            'is_vegan'      => $isVegan,
            'substitutions' => ($isVegan === 1 && $substitutions !== '') ? $substitutions : null,
        ],
        'errors' => $errors,
    ];
}
