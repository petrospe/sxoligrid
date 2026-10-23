<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDO;
use PDOException;
use RuntimeException;

final class DatabaseSnapshot
{
    private const ACADEMY_MIGRATION = '2026_10_22_000001_create_academy_tables';

    /**
     * Parent tables come first so a restored file satisfies foreign keys.
     *
     * @var list<string>
     */
    private const TABLES = [
        'migrations',
        'landing_profiles',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'billing_policies',
        'courses',
        'packages',
        'expenses',
        'events',
        'class_schedules',
        'class_schedule_teacher',
        'enrollments',
        'subscriptions_and_payments',
        'student_subscriptions',
        'private_lessons',
        'flex_suggestions',
        'event_participants',
        'lesson_logs',
        'teacher_payrolls',
        'attendances',
    ];

    public function capture(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sxoligrid-backup-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a backup file.');
        }

        unlink($path);

        $destination = new PDO('sqlite:'.$path);
        $destination->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $destination->exec('PRAGMA foreign_keys = OFF');

        $source = DB::connection()->getPdo();
        $schema = $source->query("SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY rowid");

        if ($schema === false) {
            throw new RuntimeException('Unable to read the database schema.');
        }

        foreach ($schema->fetchAll(PDO::FETCH_ASSOC) as $item) {
            if (! in_array($item['type'], ['table', 'index'], true)) {
                continue;
            }

            $this->assertIdentifier((string) $item['name']);
            $destination->exec((string) $item['sql']);
        }

        foreach (self::TABLES as $table) {
            $this->insert($destination, $table, $this->rows($source, $table));
        }

        $check = $destination->query('PRAGMA foreign_key_check');

        if ($check !== false && $check->fetchAll() !== []) {
            throw new RuntimeException('The backup failed its foreign key check.');
        }

        return $path;
    }

    public function restore(string $path): void
    {
        try {
            $backup = $this->openBackup($path);
            $source = DB::connection()->getPdo();
            $this->assertSameSchema($source, $backup);

            DB::transaction(function () use ($source, $backup): void {
                foreach (array_reverse(self::TABLES) as $table) {
                    $source->exec('DELETE FROM "'.$table.'"');
                }

                foreach (self::TABLES as $table) {
                    $this->insert($source, $table, $this->rows($backup, $table));
                }
            });
        } catch (PDOException) {
            throw ValidationException::withMessages([
                'backup' => __('ui.errors.backup_invalid'),
            ]);
        }
    }

    private function openBackup(string $path): PDO
    {
        $header = $this->header($path);

        if ($header !== "SQLite format 3\0" || preg_match('/[?#\r\n]/', $path) === 1) {
            throw ValidationException::withMessages([
                'backup' => __('ui.errors.backup_invalid'),
            ]);
        }

        $readable = str_replace('\\', '/', $path);
        $backup = new PDO('sqlite:file:'.$readable.'?mode=ro');
        $backup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $backup->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
        $statement->execute([self::ACADEMY_MIGRATION]);

        if ((int) $statement->fetchColumn() < 1) {
            throw ValidationException::withMessages([
                'backup' => __('ui.errors.backup_invalid'),
            ]);
        }

        return $backup;
    }

    private function header(string $path): string
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $header = fread($handle, 16);
        fclose($handle);

        return is_string($header) ? $header : '';
    }

    private function assertSameSchema(PDO $source, PDO $backup): void
    {
        $expected = self::TABLES;
        sort($expected);

        if ($this->tables($source) !== $expected || $this->tables($backup) !== $expected) {
            throw ValidationException::withMessages([
                'backup' => __('ui.errors.backup_invalid'),
            ]);
        }

        foreach (self::TABLES as $table) {
            if ($this->columns($source, $table) !== $this->columns($backup, $table)) {
                throw ValidationException::withMessages([
                    'backup' => __('ui.errors.backup_invalid'),
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function tables(PDO $pdo): array
    {
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        if ($statement === false) {
            return [];
        }

        return array_map(strval(...), $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return list<string>
     */
    private function columns(PDO $pdo, string $table): array
    {
        $this->assertIdentifier($table);
        $statement = $pdo->query('PRAGMA table_info("'.$table.'")');

        if ($statement === false) {
            return [];
        }

        $names = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $names[] = (string) $column['name'];
        }

        sort($names);

        return $names;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(PDO $pdo, string $table): array
    {
        $this->assertIdentifier($table);
        $statement = $pdo->query('SELECT * FROM "'.$table.'"');

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(PDO $pdo, string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->assertIdentifier($table);
        $columns = array_keys($rows[0]);

        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $quoted = implode(', ', array_map(static fn (string $column): string => '"'.$column.'"', $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $statement = $pdo->prepare('INSERT INTO "'.$table.'" ('.$quoted.') VALUES ('.$placeholders.')');

        foreach ($rows as $row) {
            $statement->execute(array_values($row));
        }
    }

    private function assertIdentifier(string $name): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new RuntimeException('Unexpected database identifier.');
        }
    }
}
