<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    if (config('database.default') !== 'mysql') {
        throw new RuntimeException('Laravel is not connected to MySQL.');
    }

    $path = database_path('database.sqlite');
    if (!is_file($path) || filesize($path) === 0) {
        throw new RuntimeException('Original SQLite database not found.');
    }

    $sqlite = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($sqlite->query('PRAGMA foreign_key_check')->fetchAll()) {
        throw new RuntimeException('SQLite has invalid foreign-key references.');
    }

    $mysql = DB::connection('mysql');

    $skip = [
        'migrations', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'
    ];

    $tables = $sqlite->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
    )->fetchAll(PDO::FETCH_COLUMN);

    $plan = [];

    foreach ($tables as $table) {
        if (in_array($table, $skip, true)) continue;

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new RuntimeException("Invalid table name: $table");
        }

        if (!Schema::connection('mysql')->hasTable($table)) {
            throw new RuntimeException("Missing MySQL table: $table");
        }

        $sqliteColumns = array_column(
            $sqlite->query('PRAGMA table_info("' . $table . '")')->fetchAll(),
            'name'
        );

        $mysqlColumns = Schema::connection('mysql')
            ->getColumnListing($table);

        if (array_diff($sqliteColumns, $mysqlColumns)) {
            throw new RuntimeException("Different columns in $table; import stopped.");
        }

        if ($mysql->table($table)->count() > 0) {
            throw new RuntimeException("MySQL table $table already has data; import stopped.");
        }

        $plan[$table] = (int) $sqlite->query(
            'SELECT COUNT(*) FROM "' . $table . '"'
        )->fetchColumn();
    }

    echo 'Transferring ' . array_sum($plan) . " rows...\n";

    $mysql->statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        $mysql->transaction(function () use ($sqlite, $mysql, $plan) {
            foreach ($plan as $table => $expected) {
                $query = $sqlite->query('SELECT * FROM "' . $table . '"');
                $batch = [];
                $copied = 0;

                while ($row = $query->fetch()) {
                    $batch[] = $row;

                    if (count($batch) >= 100) {
                        $mysql->table($table)->insert($batch);
                        $copied += count($batch);
                        $batch = [];
                    }
                }

                if ($batch) {
                    $mysql->table($table)->insert($batch);
                    $copied += count($batch);
                }

                if (
                    $copied !== $expected ||
                    $mysql->table($table)->count() !== $expected
                ) {
                    throw new RuntimeException("Row count mismatch in $table");
                }

                echo "$table: $copied rows\n";
            }
        });
    } finally {
        $mysql->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    echo "SUCCESS — records copied to MySQL.\n";

} catch (Throwable $e) {
    fwrite(STDERR, "IMPORT STOPPED: " . $e->getMessage() . "\n");
    exit(1);
}