<?php
declare(strict_types=1);

/**
 * Практична робота №7: Єдина точка входу REST API з профілюванням, кешем та усуненням N+1.
 * Варіант №6: Кулінарна книга (resource=recipes)
 */

$requestStartTime = microtime(true);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/Cache.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/classes/Cookbook.php';

$cache    = new FileCache('cookbook_lab7_');
$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? null;
$id       = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$action   = $_GET['action'] ?? null;

function sendApiResponse(
    bool $success,
    mixed $payload,
    int $status = 200,
    array $meta = []
): never {
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

function buildProfilerMeta(
    TrackedPDO $pdo,
    FileCache $cache,
    float $startTime,
    string $mode,
    bool $cacheHit
): array {
    $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
    $peakMemMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

    return [
        'mode'               => $mode,
        'query_count'        => $pdo->getQueryCount(),
        'sql_time_ms'        => $pdo->getTotalSqlTimeMs(),
        'execution_time_ms'  => $elapsedMs,
        'peak_memory_mb'     => $peakMemMb,
        'cache_hit'          => $cacheHit,
        'active_cache_files' => $cache->getActiveFilesCount(45),
        'queries_sample'     => array_slice($pdo->getQueryLog(), 0, 10),
    ];
}

if ($resource !== 'recipes') {
    sendApiResponse(false, 'Невідомий ресурс. Доступний ресурс: recipes', 404);
}

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $cache, $id, $action, $requestStartTime);
            break;

        case 'POST':
            handlePost($pdo, $cache, $action, $requestStartTime);
            break;

        case 'PUT':
            handleUpdate($pdo, $cache, $id, json_decode(file_get_contents('php://input'), true) ?? [], $requestStartTime);
            break;

        case 'DELETE':
            handleDelete($pdo, $cache, $id, json_decode(file_get_contents('php://input'), true) ?? [], $requestStartTime);
            break;

        default:
            sendApiResponse(false, "HTTP-метод {$method} не підтримується.", 405);
    }
} catch (PDOException $e) {
    error_log('API PDOException: ' . $e->getMessage());
    sendApiResponse(false, 'Внутрішня помилка бази даних.', 500);
}

/**
 * Отримання кешованого списку найшвидших рецептів (shortestCookTime — Крок 6 ЛР №7).
 * Використовує індекс idx_recipes_cook_time при промаху кешу.
 *
 * @return array<int, array<string, mixed>>
 */
function getCachedShortestRecipes(TrackedPDO $pdo, FileCache $cache, bool $useCache, int $limit = 3): array
{
    $cacheKey = "shortest_recipes_limit_{$limit}";
    if ($useCache) {
        $cached = $cache->get($cacheKey, 45);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM recipes ORDER BY cook_time_min ASC LIMIT :lim');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[] = Cookbook::createFromDbRow($row)->toArray();
    }

    if ($useCache) {
        $cache->set($cacheKey, $result);
    }

    return $result;
}

/**
 * GET /api.php?resource=recipes
 */
function handleGet(TrackedPDO $pdo, FileCache $cache, ?int $id, ?string $action, float $startTime): never
{
    $mode = ($_GET['mode'] ?? 'optimized') === 'unoptimized' ? 'unoptimized' : 'optimized';
    $useCache = ($mode === 'optimized') && (($_GET['nocache'] ?? '0') !== '1');

    // Одиничний запис за ID
    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM recipes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            sendApiResponse(false, "Рецепт з ID {$id} не знайдено.", 404);
        }
        $recipeObj = Cookbook::createFromDbRow($row);
        sendApiResponse(
            true,
            $recipeObj->toArray(),
            200,
            buildProfilerMeta($pdo, $cache, $startTime, $mode, false)
        );
    }

    // Доменна дія: GET ?resource=recipes&action=shortest (Кешований shortestCookTime)
    if ($action === 'shortest') {
        $shortestList = getCachedShortestRecipes($pdo, $cache, $useCache, 3);
        $cacheHit = $useCache && ($pdo->getQueryCount() === 0);
        sendApiResponse(
            true,
            $shortestList,
            200,
            buildProfilerMeta($pdo, $cache, $startTime, $mode, $cacheHit)
        );
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    $maxTimeRaw = trim((string) ($_GET['max_time'] ?? ''));
    $maxTime = ($maxTimeRaw !== '' && is_numeric($maxTimeRaw) && (int) $maxTimeRaw > 0)
        ? (int) $maxTimeRaw
        : null;

    $cacheKey = 'list_' . md5("{$q}|{$maxTime}");

    // Крок 6 (ЛР №7): Повернення з файлового кешу у режимі optimized
    if ($useCache) {
        $cachedPayload = $cache->get($cacheKey, 45);
        if (is_array($cachedPayload)) {
            sendApiResponse(
                true,
                $cachedPayload,
                200,
                buildProfilerMeta($pdo, $cache, $startTime, $mode, true)
            );
        }
    }

    // Формування основного SELECT
    $conditions = [];
    $params = [];

    if ($q !== '') {
        $conditions[] = '(ingredients LIKE :q_ing OR title LIKE :q_title)';
        $params[':q_ing'] = '%' . $q . '%';
        $params[':q_title'] = '%' . $q . '%';
    }

    // Використовує індекс idx_recipes_cook_time (type=range у EXPLAIN)
    if ($maxTime !== null) {
        $conditions[] = 'cook_time_min <= :max_time';
        $params[':max_time'] = $maxTime;
    }

    $sql = 'SELECT * FROM recipes';
    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $cookbook = new Cookbook();
    $enrichedRecipes = [];

    if ($mode === 'unoptimized') {
        // =====================================================================
        // РЕЖИМ «ДО ОПТИМІЗАЦІЇ» (Крок 3 ЛР №7 — Демонстрація проблеми N+1):
        // Для кожного рецепта виконується окремий SQL-запит LIKE у циклі!
        // =====================================================================
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM recipes WHERE ingredients LIKE :pattern AND id != :current_id'
        );

        foreach ($rows as $row) {
            $recipeObj = Cookbook::createFromDbRow($row);
            $cookbook->addRecipe($recipeObj);

            $ingredientNames = array_keys($recipeObj->getIngredients());
            $primaryIng = $ingredientNames[0] ?? '';
            $similarCount = 0;

            if ($primaryIng !== '') {
                $countStmt->execute([
                    ':pattern'    => '%' . $primaryIng . '%',
                    ':current_id' => (int) $row['id'],
                ]);
                $similarCount = (int) $countStmt->fetchColumn();
            }

            $item = $recipeObj->toArray();
            $item['primary_ingredient'] = $primaryIng;
            $item['similar_recipes_count'] = $similarCount;
            $enrichedRecipes[] = $item;
        }
    } else {
        // =====================================================================
        // РЕЖИМ «ПІСЛЯ ОПТИМІЗАЦІЇ» (Крок 5 ЛР №7 — Усунення N+1):
        // Жодного SQL-запиту в циклі: частота інгредієнтів рахується за 1 прохід O(N) у пам'яті.
        // =====================================================================
        $ingredientFrequency = [];
        $parsedItems = [];

        foreach ($rows as $row) {
            $recipeObj = Cookbook::createFromDbRow($row);
            $cookbook->addRecipe($recipeObj);
            $ingredientNames = array_keys($recipeObj->getIngredients());
            $primaryIng = $ingredientNames[0] ?? '';
            $normKey = mb_strtolower(trim($primaryIng));

            if ($normKey !== '') {
                $ingredientFrequency[$normKey] = ($ingredientFrequency[$normKey] ?? 0) + 1;
            }

            $parsedItems[] = [
                'obj'        => $recipeObj,
                'primaryIng' => $primaryIng,
                'normKey'    => $normKey,
            ];
        }

        foreach ($parsedItems as $entry) {
            $item = $entry['obj']->toArray();
            $normKey = $entry['normKey'];
            $item['primary_ingredient'] = $entry['primaryIng'];
            $item['similar_recipes_count'] = $normKey !== ''
                ? max(0, ($ingredientFrequency[$normKey] ?? 1) - 1)
                : 0;
            $enrichedRecipes[] = $item;
        }
    }

    // Кешований список найшвидших рецептів (shortestCookTime)
    $shortestTop = getCachedShortestRecipes($pdo, $cache, $useCache, 3);
    $fastestRecipe = $cookbook->shortestCookTime();

    $payload = [
        'recipes' => $enrichedRecipes,
        'stats'   => [
            'total_count'        => $cookbook->count(),
            'average_cook_time'  => $cookbook->getAverageCookTime(),
            'quick_count'        => $cookbook->getQuickRecipesCount(),
            'fastest_recipe'     => $fastestRecipe ? $fastestRecipe->toArray() : null,
            'shortest_top3'      => $shortestTop,
        ],
    ];

    if ($useCache) {
        $cache->set($cacheKey, $payload);
    }

    sendApiResponse(
        true,
        $payload,
        200,
        buildProfilerMeta($pdo, $cache, $startTime, $mode, false)
    );
}

/**
 * POST /api.php?resource=recipes (створення, пошук, оновлення, видалення, скидання кешу)
 */
function handlePost(TrackedPDO $pdo, FileCache $cache, ?string $action, float $startTime): never
{
    $rawBody = file_get_contents('php://input');
    $jsonBody = ($rawBody !== false && $rawBody !== '') ? json_decode($rawBody, true) : null;
    $input = is_array($jsonBody) ? $jsonBody : $_POST;

    // Очищення кешу вручну (для тестування ЛР №7)
    if ($action === 'clear_cache') {
        $removed = $cache->clearAll();
        sendApiResponse(
            true,
            ['cleared_files' => $removed],
            200,
            buildProfilerMeta($pdo, $cache, $startTime, 'optimized', false)
        );
    }

    // Доменна дія з ЛР №6: пошук за максимальним часом приготування
    if ($action === 'search') {
        $maxTime = $input['max_time'] ?? $_GET['max_time'] ?? null;
        if ($maxTime === null || !is_numeric($maxTime) || (int) $maxTime <= 0) {
            sendApiResponse(false, "Параметр 'max_time' має бути цілим числом більше 0.", 400);
        }

        // Використовує індекс idx_recipes_cook_time
        $stmt = $pdo->prepare('SELECT * FROM recipes WHERE cook_time_min <= :max_time ORDER BY cook_time_min ASC');
        $stmt->execute([':max_time' => (int) $maxTime]);
        $rows = $stmt->fetchAll();

        $recipes = [];
        foreach ($rows as $row) {
            $recipes[] = Cookbook::createFromDbRow($row)->toArray();
        }

        sendApiResponse(
            true,
            $recipes,
            200,
            buildProfilerMeta($pdo, $cache, $startTime, 'optimized', false)
        );
    }

    // Оновлення рецепта через POST ?action=update
    if ($action === 'update') {
        $id = isset($input['id']) && is_numeric($input['id']) ? (int) $input['id'] : null;
        handleUpdate($pdo, $cache, $id, $input, $startTime);
    }

    // Видалення рецепта через POST ?action=delete
    if ($action === 'delete') {
        $id = isset($input['id']) && is_numeric($input['id']) ? (int) $input['id'] : null;
        handleDelete($pdo, $cache, $id, $input, $startTime);
    }

    // Створення нового рецепта (INSERT)
    if ($action === null || $action === '') {
        $validated = validateRecipePayload($input);
        if (!empty($validated['errors'])) {
            sendApiResponse(false, 'Помилка валідації даних рецепта.', 400, $validated['errors']);
        }

        $data = $validated['data'];
        $sql = 'INSERT INTO recipes (title, ingredients, cook_time_min, servings, difficulty, is_vegan, substitutions)
                VALUES (:title, :ingredients, :cook_time_min, :servings, :difficulty, :is_vegan, :substitutions)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'         => $data['title'],
            ':ingredients'   => $data['ingredients'],
            ':cook_time_min' => $data['cook_time_min'],
            ':servings'      => $data['servings'],
            ':difficulty'    => $data['difficulty'],
            ':is_vegan'      => $data['is_vegan'],
            ':substitutions' => $data['substitutions'],
        ]);

        $newId = (int) $pdo->lastInsertId();
        $data['id'] = $newId;

        // Крок 6 (ЛР №7): Інвалідація кешу після зміни даних
        $cache->clearAll();

        $createdObj = Cookbook::createFromDbRow($data);
        sendApiResponse(
            true,
            $createdObj->toArray(),
            201,
            buildProfilerMeta($pdo, $cache, $startTime, 'optimized', false)
        );
    }

    sendApiResponse(false, 'Невідома дія action для POST.', 400);
}

/**
 * Оновлення запису (UPDATE) + інвалідація кешу
 */
function handleUpdate(TrackedPDO $pdo, FileCache $cache, ?int $id, array $input, float $startTime): never
{
    if ($id === null || $id <= 0) {
        sendApiResponse(false, 'Не вказано коректний ID рецепта для оновлення.', 400);
    }

    $check = $pdo->prepare('SELECT id FROM recipes WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        sendApiResponse(false, "Рецепт з ID {$id} не знайдено.", 404);
    }

    $validated = validateRecipePayload($input);
    if (!empty($validated['errors'])) {
        sendApiResponse(false, 'Помилка валідації при оновленні.', 400, $validated['errors']);
    }

    $data = $validated['data'];
    $sql = 'UPDATE recipes
            SET title = :title,
                ingredients = :ingredients,
                cook_time_min = :cook_time_min,
                servings = :servings,
                difficulty = :difficulty,
                is_vegan = :is_vegan,
                substitutions = :substitutions
            WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'            => $id,
        ':title'         => $data['title'],
        ':ingredients'   => $data['ingredients'],
        ':cook_time_min' => $data['cook_time_min'],
        ':servings'      => $data['servings'],
        ':difficulty'    => $data['difficulty'],
        ':is_vegan'      => $data['is_vegan'],
        ':substitutions' => $data['substitutions'],
    ]);

    // Інвалідація кешу
    $cache->clearAll();

    $data['id'] = $id;
    $updatedObj = Cookbook::createFromDbRow($data);
    sendApiResponse(
        true,
        $updatedObj->toArray(),
        200,
        buildProfilerMeta($pdo, $cache, $startTime, 'optimized', false)
    );
}

/**
 * Видалення запису (DELETE) + інвалідація кешу
 */
function handleDelete(TrackedPDO $pdo, FileCache $cache, ?int $id, array $input, float $startTime): never
{
    $targetId = $id ?? (isset($input['id']) && is_numeric($input['id']) ? (int) $input['id'] : null);
    if ($targetId === null || $targetId <= 0) {
        sendApiResponse(false, 'Не вказано коректний ID рецепта для видалення.', 400);
    }

    $stmt = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
    $stmt->execute([':id' => $targetId]);

    if ($stmt->rowCount() === 0) {
        sendApiResponse(false, "Рецепт з ID {$targetId} не знайдено.", 404);
    }

    // Інвалідація кешу
    $cache->clearAll();

    sendApiResponse(
        true,
        ['deleted_id' => $targetId],
        200,
        buildProfilerMeta($pdo, $cache, $startTime, 'optimized', false)
    );
}

/**
 * Серверна валідація полів рецепта (ЛР №2 + ЛР №4 + ЛР №6)
 *
 * @return array{data: array<string, mixed>, errors: array<string, string>}
 */
function validateRecipePayload(array $input): array
{
    $errors = [];

    $title = trim((string) ($input['title'] ?? ''));
    $ingredients = trim((string) ($input['ingredients'] ?? ''));
    $cookTimeRaw = $input['cook_time_min'] ?? null;
    $servingsRaw = $input['servings'] ?? 2;
    $difficulty = trim((string) ($input['difficulty'] ?? 'Середній'));
    $isVegan = !empty($input['is_vegan']) && (string) $input['is_vegan'] !== '0' ? 1 : 0;
    $substitutions = trim((string) ($input['substitutions'] ?? ''));

    if ($title === '') {
        $errors['title'] = 'Назва рецепта є обов\'язковою.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 255) {
        $errors['title'] = 'Назва повинна містити від 3 до 255 символів.';
    }

    if ($ingredients === '') {
        $errors['ingredients'] = 'Перелік інгредієнтів не може бути порожнім.';
    } else {
        $parsed = parseIngredientsString($ingredients);
        if ($parsed === []) {
            $errors['ingredients'] = 'Вкажіть хоча б один непорожній інгредієнт.';
        }
    }

    if ($cookTimeRaw === null || $cookTimeRaw === '' || filter_var($cookTimeRaw, FILTER_VALIDATE_INT) === false) {
        $errors['cook_time_min'] = 'Час приготування має бути цілим числом.';
    } elseif ((int) $cookTimeRaw <= 0 || (int) $cookTimeRaw > 1440) {
        $errors['cook_time_min'] = 'Час приготування має бути від 1 до 1440 хв.';
    }

    $servings = filter_var($servingsRaw, FILTER_VALIDATE_INT);
    if ($servings === false || $servings < 1 || $servings > 100) {
        $servings = 2;
    }

    $allowedDifficulties = ['Легкий', 'Середній', 'Складний'];
    if (!in_array($difficulty, $allowedDifficulties, true)) {
        $difficulty = 'Середній';
    }

    return [
        'data' => [
            'title'         => $title,
            'ingredients'   => $ingredients,
            'cook_time_min' => (int) $cookTimeRaw,
            'servings'      => (int) $servings,
            'difficulty'    => $difficulty,
            'is_vegan'      => $isVegan,
            'substitutions' => $isVegan === 1 && $substitutions !== '' ? $substitutions : null,
        ],
        'errors' => $errors,
    ];
}
