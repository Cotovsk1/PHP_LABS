<?php
declare(strict_types=1);

/**
 * Практична робота №8 (Крок 6): Підключення PDO без витоку сирих винятків користувачу.
 */

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum8';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

class TrackedPDOStatement extends PDOStatement
{
    private TrackedPDO $trackedPdo;

    protected function __construct(TrackedPDO $pdo)
    {
        $this->trackedPdo = $pdo;
    }

    public function execute(?array $params = null): bool
    {
        $start = microtime(true);
        $result = parent::execute($params);
        $this->trackedPdo->recordQuery((microtime(true) - $start) * 1000);
        return $result;
    }
}

class TrackedPDO extends PDO
{
    private int $queryCount = 0;
    private float $totalSqlTimeMs = 0.0;

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [TrackedPDOStatement::class, [$this]]);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $start = microtime(true);
        $stmt = $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
        $this->recordQuery((microtime(true) - $start) * 1000);
        return $stmt;
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $res = parent::exec($statement);
        $this->recordQuery((microtime(true) - $start) * 1000);
        return $res;
    }

    public function recordQuery(float $timeMs): void
    {
        $this->queryCount++;
        $this->totalSqlTimeMs += $timeMs;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getTotalSqlTimeMs(): float
    {
        return round($this->totalSqlTimeMs, 3);
    }
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new TrackedPDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Крок 6 ЛР №8: Деталі пишуться виключно в серверний лог, користувач отримує узагальнене повідомлення
    error_log('[SecurityAudit] DB connection error: ' . $e->getMessage());
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api.php')) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Сервіс бази даних тимчасово недоступний. Зверніться до адміністратора або виконайте ініціалізацію БД.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: setup.php?notice=db_missing');
    exit;
}
