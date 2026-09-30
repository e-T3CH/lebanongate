<?php

declare(strict_types=1);

namespace BMMatic\Core;

/**
 * Runs database/migrations/*.php in filename order and records them in the `migrations` table.
 * MySQL commits DDL implicitly, so each migration must be safe to re-run after a partial failure
 * (CREATE TABLE IF NOT EXISTS etc.).
 */
final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $directory)
    {
    }

    public static function default(Database $db): self
    {
        return new self($db, Paths::root('database/migrations'));
    }

    /** @return list<string> migration names that were run */
    public function migrate(): array
    {
        $this->ensureTable();
        $done = $this->ran();
        $pending = array_values(array_diff($this->available(), $done));
        if ($pending === []) {
            return [];
        }
        $batch = (int) $this->db->scalar('SELECT COALESCE(MAX(batch), 0) FROM {migrations}') + 1;
        foreach ($pending as $name) {
            $this->load($name)->up($this->db);
            $this->db->insert('migrations', ['migration' => $name, 'batch' => $batch, 'ran_at' => gmdate('Y-m-d H:i:s')]);
        }
        return $pending;
    }

    /**
     * Rolls back the last batch.
     *
     * @return list<string>
     */
    public function rollback(): array
    {
        $this->ensureTable();
        $batch = (int) $this->db->scalar('SELECT COALESCE(MAX(batch), 0) FROM {migrations}');
        if ($batch === 0) {
            return [];
        }
        $rows = $this->db->select('migrations', ['batch' => $batch], ['migration'], ['migration' => 'DESC']);
        $names = [];
        foreach ($rows as $row) {
            $name = (string) $row['migration'];
            $this->load($name)->down($this->db);
            $this->db->delete('migrations', ['migration' => $name]);
            $names[] = $name;
        }
        return $names;
    }

    /** @return array<string, bool> name => has run */
    public function status(): array
    {
        $this->ensureTable();
        $ran = $this->ran();
        $status = [];
        foreach ($this->available() as $name) {
            $status[$name] = in_array($name, $ran, true);
        }
        return $status;
    }

    /** @return list<string> */
    public function available(): array
    {
        $files = glob(rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $names = array_map(static fn (string $f): string => basename($f, '.php'), $files);
        sort($names, SORT_STRING);
        return $names;
    }

    /** @return list<string> */
    private function ran(): array
    {
        return array_map(static fn (array $r): string => (string) $r['migration'], $this->db->select('migrations', [], ['migration']));
    }

    private function ensureTable(): void
    {
        $this->db->run('CREATE TABLE IF NOT EXISTS {migrations} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(190) NOT NULL,
            `batch` INT UNSIGNED NOT NULL,
            `ran_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `migrations_migration_unique` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function load(string $name): Migration
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid migration name: ' . $name);
        }
        $migration = require rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $name . '.php';
        if (!$migration instanceof Migration) {
            throw new \RuntimeException('Migration ' . $name . ' must return an instance of ' . Migration::class);
        }
        return $migration;
    }
}
