<?php

declare(strict_types=1);

namespace BMMatic\Core;

use PDO;
use PDOStatement;

/**
 * PDO singleton. Rules enforced here:
 *  - table names only from the whitelist (Tables::ALLOWED); raw SQL refers to tables as {table} placeholders;
 *  - column names must be plain identifiers;
 *  - values are always bound parameters (native prepared statements, emulation off).
 */
final class Database
{
    private static ?self $instance = null;

    private const OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'IN', 'IS NULL', 'IS NOT NULL'];

    private function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $config keys: host, port, name, user, pass, charset */
    public static function connect(array $config): self
    {
        self::$instance = new self(self::createPdo($config));
        return self::$instance;
    }

    public static function fromPdo(PDO $pdo): self
    {
        self::$instance = new self($pdo);
        return self::$instance;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database is not connected.');
        }
        return self::$instance;
    }

    public static function isConnected(): bool
    {
        return self::$instance !== null;
    }

    public static function disconnect(): void
    {
        self::$instance = null;
    }

    /** @param array<string, mixed> $config */
    public static function createPdo(array $config, bool $withDatabase = true): PDO
    {
        $host = is_scalar($config['host'] ?? null) ? (string) $config['host'] : '127.0.0.1';
        $port = is_numeric($config['port'] ?? null) ? (int) $config['port'] : 3306;
        $name = is_scalar($config['name'] ?? null) ? (string) $config['name'] : '';
        $charset = 'utf8mb4';
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
        if ($withDatabase) {
            $dsn .= ';dbname=' . $name;
        }
        $pdo = new PDO($dsn, is_scalar($config['user'] ?? null) ? (string) $config['user'] : '', is_scalar($config['pass'] ?? null) ? (string) $config['pass'] : '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+00:00', sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        return $pdo;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Backtick-quoted, whitelisted table name. */
    public function table(string $name): string
    {
        if (!Tables::isAllowed($name)) {
            throw new \InvalidArgumentException('Table is not whitelisted: ' . $name);
        }
        return '`' . $name . '`';
    }

    /** Backtick-quoted column identifier. */
    public function column(string $name): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]{0,63}$/', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid column name: ' . $name);
        }
        return '`' . $name . '`';
    }

    /**
     * Runs a prepared statement. Tables are written as {name} and resolved through the whitelist.
     *
     * @param array<string|int, mixed> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $sql = (string) preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/', fn (array $m): string => $this->table($m[1]), $sql);
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : (str_starts_with($key, ':') ? $key : ':' . $key);
            $stmt->bindValue($name, $value, match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            });
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->run($sql, $params)->fetchAll();
        return $rows;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string|int, mixed> $params */
    public function scalar(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /**
     * SELECT helper. $where keys are "column" or "column OPERATOR" (=, !=, <, <=, >, >=, LIKE, IN, IS NULL, IS NOT NULL).
     *
     * @param array<string, mixed> $where
     * @param list<string> $columns
     * @param array<string, 'ASC'|'DESC'> $orderBy
     * @return list<array<string, mixed>>
     */
    public function select(string $table, array $where = [], array $columns = ['*'], array $orderBy = [], ?int $limit = null): array
    {
        $cols = $columns === ['*'] ? '*' : implode(', ', array_map(fn (string $c): string => $this->column($c), $columns));
        [$whereSql, $params] = $this->buildWhere($where);
        $sql = 'SELECT ' . $cols . ' FROM ' . $this->table($table) . $whereSql;
        if ($orderBy !== []) {
            $parts = [];
            foreach ($orderBy as $col => $dir) {
                $parts[] = $this->column($col) . ($dir === 'DESC' ? ' DESC' : ' ASC');
            }
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit);
        }
        return $this->all($sql, $params);
    }

    /**
     * @param array<string, mixed> $where
     * @param list<string> $columns
     * @return array<string, mixed>|null
     */
    public function first(string $table, array $where = [], array $columns = ['*']): ?array
    {
        return $this->select($table, $where, $columns, [], 1)[0] ?? null;
    }

    /** @param array<string, mixed> $data */
    public function insert(string $table, array $data): int
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Nothing to insert.');
        }
        $cols = [];
        $marks = [];
        $params = [];
        $i = 0;
        foreach ($data as $col => $value) {
            $cols[] = $this->column($col);
            $marks[] = ':v' . $i;
            $params['v' . $i] = $value;
            $i++;
        }
        $this->run('INSERT INTO ' . $this->table($table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $marks) . ')', $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * INSERT ... ON DUPLICATE KEY UPDATE for the given columns.
     *
     * @param array<string, mixed> $data
     * @param list<string> $updateColumns
     */
    public function upsert(string $table, array $data, array $updateColumns): void
    {
        $cols = [];
        $marks = [];
        $params = [];
        $i = 0;
        foreach ($data as $col => $value) {
            $cols[] = $this->column($col);
            $marks[] = ':v' . $i;
            $params['v' . $i] = $value;
            $i++;
        }
        $updates = array_map(fn (string $c): string => $this->column($c) . ' = VALUES(' . $this->column($c) . ')', $updateColumns);
        $this->run('INSERT INTO ' . $this->table($table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $marks) . ')'
            . ($updates === [] ? '' : ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates)), $params);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int
    {
        if ($where === []) {
            throw new \InvalidArgumentException('UPDATE without WHERE is not allowed.');
        }
        $sets = [];
        $params = [];
        $i = 0;
        foreach ($data as $col => $value) {
            $sets[] = $this->column($col) . ' = :s' . $i;
            $params['s' . $i] = $value;
            $i++;
        }
        [$whereSql, $whereParams] = $this->buildWhere($where);
        return $this->run('UPDATE ' . $this->table($table) . ' SET ' . implode(', ', $sets) . $whereSql, $params + $whereParams)->rowCount();
    }

    /** @param array<string, mixed> $where */
    public function delete(string $table, array $where): int
    {
        if ($where === []) {
            throw new \InvalidArgumentException('DELETE without WHERE is not allowed.');
        }
        [$whereSql, $params] = $this->buildWhere($where);
        return $this->run('DELETE FROM ' . $this->table($table) . $whereSql, $params)->rowCount();
    }

    /**
     * @template T
     * @param callable(self): T $fn
     * @return T
     */
    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $where
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $where): array
    {
        if ($where === []) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        $i = 0;
        foreach ($where as $key => $value) {
            $key = trim($key);
            $op = '=';
            if (preg_match('/^([a-z_][a-z0-9_]*)\s+(.+)$/i', $key, $m) === 1) {
                $key = $m[1];
                $op = strtoupper(trim($m[2]));
            }
            if (!in_array($op, self::OPERATORS, true)) {
                throw new \InvalidArgumentException('Unsupported operator: ' . $op);
            }
            $col = $this->column($key);
            if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                $parts[] = $col . ' ' . $op;
            } elseif ($op === 'IN') {
                if (!is_array($value) || $value === []) {
                    $parts[] = '1 = 0';
                    continue;
                }
                $marks = [];
                foreach (array_values($value) as $j => $item) {
                    $marks[] = ':w' . $i . '_' . $j;
                    $params['w' . $i . '_' . $j] = $item;
                }
                $parts[] = $col . ' IN (' . implode(', ', $marks) . ')';
            } elseif ($value === null && $op === '=') {
                $parts[] = $col . ' IS NULL';
            } else {
                $parts[] = $col . ' ' . $op . ' :w' . $i;
                $params['w' . $i] = $value;
            }
            $i++;
        }
        return [' WHERE ' . implode(' AND ', $parts), $params];
    }
}
