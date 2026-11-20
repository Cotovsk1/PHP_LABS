<?php
declare(strict_types=1);

/**
 * Підключення до MySQL через PDO з обгорткою профілювання SQL-запитів (ЛР №4 + ЛР №7).
 */

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'practicum7';
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
        $elapsedMs = (microtime(true) - $start) * 1000;
        $this->trackedPdo->recordQuery($this->queryString, $elapsedMs);
        return $result;
    }
}

class TrackedPDO extends PDO
{
    private int $queryCount = 0;
    private float $totalSqlTimeMs = 0.0;
    /** @var array<int, array{sql: string, time_ms: float}> */
    private array $queryLog = [];

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [TrackedPDOStatement::class, [$this]]);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $start = microtime(true);
        $stmt = $fetchMode === null
            ? parent::query($query)
            : parent::query($query, $fetchMode, ...$fetchModeArgs);
        $elapsedMs = (microtime(true) - $start) * 1000;
        $this->recordQuery($query, $elapsedMs);
        return $stmt;
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $res = parent::exec($statement);
        $elapsedMs = (microtime(true) - $start) * 1000;
        $this->recordQuery($statement, $elapsedMs);
        return $res;
    }

    public function recordQuery(string $sql, float $timeMs): void
    {
        $this->queryCount++;
        $this->totalSqlTimeMs += $timeMs;
        if (count($this->queryLog) < 50) {
            $this->queryLog[] = [
                'sql'     => preg_replace('/\s+/', ' ', trim($sql)) ?? $sql,
                'time_ms' => round($timeMs, 3),
            ];
        }
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getTotalSqlTimeMs(): float
    {
        return round($this->totalSqlTimeMs, 3);
    }

    /** @return array<int, array{sql: string, time_ms: float}> */
    public function getQueryLog(): array
    {
        return $this->queryLog;
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
    error_log('DB connection failed: ' . $e->getMessage());
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api.php')) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Не вдалося підключитися до бази даних. Перевірте MySQL або запустіть setup.php.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: setup.php?notice=db_missing');
    exit;
}
