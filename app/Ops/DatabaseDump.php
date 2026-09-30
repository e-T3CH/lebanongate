<?php

declare(strict_types=1);

namespace Gate\Ops;

use Gate\Core\Database;
use Gate\Core\Tables;

/**
 * A plain SQL dump written in PHP, so backups work on shared hosting where `mysqldump` is not available.
 *
 * One statement per line: CREATE TABLE is collapsed onto one line and PDO::quote() escapes line breaks inside
 * values, so a restore can read the file line by line without an SQL parser. Binary columns are written as hex.
 * Only the application's own tables (the whitelist in Core\Tables) are dumped and restored.
 */
final class DatabaseDump
{
    private const BATCH = 200;

    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<string> the application tables that exist in this database */
    public function tables(): array
    {
        $existing = [];
        foreach ($this->db->pdo()->query('SHOW TABLES')?->fetchAll(\PDO::FETCH_COLUMN) ?: [] as $name) {
            if (is_string($name) && in_array($name, Tables::ALLOWED, true)) {
                $existing[] = $name;
            }
        }
        return $existing;
    }

    /**
     * Writes the dump to $file and returns the row count per table.
     *
     * @return array<string, int>
     */
    public function write(string $file): array
    {
        $out = fopen($file, 'wb');
        if ($out === false) {
            throw new \RuntimeException('Cannot write the database dump to ' . $file);
        }
        $pdo = $this->db->pdo();
        fwrite($out, "-- GATE Lebanon database dump, " . gmdate('Y-m-d H:i:s') . " UTC\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n");
        $counts = [];
        foreach ($this->tables() as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')?->fetch(\PDO::FETCH_NUM);
            if (!is_array($create) || !is_string($create[1] ?? null)) {
                throw new \RuntimeException('Cannot read the structure of ' . $table);
            }
            fwrite($out, 'DROP TABLE IF EXISTS `' . $table . "`;\n");
            fwrite($out, str_replace(["\r\n", "\n"], ' ', $create[1]) . ";\n");
            $binary = $this->binaryColumns($table);
            $counts[$table] = 0;
            $batch = [];
            $columns = null;
            $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
            while ($stmt !== false && is_array($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
                $columns ??= '(`' . implode('`, `', array_keys($row)) . '`)';
                $values = [];
                foreach ($row as $column => $value) {
                    $values[] = match (true) {
                        $value === null => 'NULL',
                        is_int($value), is_float($value) => (string) $value,
                        in_array($column, $binary, true) => $value === '' ? "''" : '0x' . bin2hex((string) $value),
                        default => $pdo->quote((string) $value),
                    };
                }
                $batch[] = '(' . implode(', ', $values) . ')';
                $counts[$table]++;
                if (count($batch) >= self::BATCH) {
                    fwrite($out, 'INSERT INTO `' . $table . '` ' . $columns . ' VALUES ' . implode(', ', $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch !== []) {
                fwrite($out, 'INSERT INTO `' . $table . '` ' . $columns . ' VALUES ' . implode(', ', $batch) . ";\n");
            }
        }
        fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n-- end of dump\n");
        fclose($out);
        return $counts;
    }

    /**
     * Replaces every application table with the contents of a dump written by write().
     *
     * @return int statements executed
     */
    public function restore(string $file): int
    {
        $in = fopen($file, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Cannot read ' . $file);
        }
        $first = (string) fgets($in);
        if (!str_starts_with($first, '-- GATE Lebanon database dump')) {
            fclose($in);
            throw new \RuntimeException('This is not a GATE Lebanon database dump.');
        }
        $pdo = $this->db->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        // Tables that exist now but not in the dump (a newer migration) would survive otherwise.
        foreach ($this->tables() as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }
        $count = 0;
        $complete = false;
        while (($line = fgets($in)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '-- end of dump') {
                $complete = true;
                continue;
            }
            if ($line === '' || str_starts_with($line, '-- ')) {
                continue;
            }
            $pdo->exec($line);
            $count++;
        }
        fclose($in);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        if (!$complete) {
            throw new \RuntimeException('The dump ends early (the backup file is incomplete). The database may be partly restored.');
        }
        return $count;
    }

    /**
     * A checksum of every table's content, to prove a restore gives back the same data.
     *
     * @return array<string, string> table => checksum
     */
    public function checksums(): array
    {
        $out = [];
        foreach ($this->tables() as $table) {
            $row = $this->db->pdo()->query('CHECKSUM TABLE `' . $table . '`')?->fetch(\PDO::FETCH_ASSOC);
            $out[$table] = is_array($row) ? (string) ($row['Checksum'] ?? '') : '';
        }
        ksort($out);
        return $out;
    }

    /** @return list<string> */
    private function binaryColumns(string $table): array
    {
        $out = [];
        foreach ($this->db->pdo()->query('SHOW COLUMNS FROM `' . $table . '`')?->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $column) {
            if (is_array($column) && preg_match('/^(binary|varbinary|tinyblob|blob|mediumblob|longblob)/i', (string) ($column['Type'] ?? '')) === 1) {
                $out[] = (string) $column['Field'];
            }
        }
        return $out;
    }
}
