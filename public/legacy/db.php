<?php

declare(strict_types=1);

/**
 * Minimal PDO bootstrap for legacy PHP scripts (reads Laravel .env).
 */
if (! function_exists('legacy_pdo')) {
    function legacy_pdo(): PDO
    {
        $envPath = dirname(__DIR__, 2).'/.env';
        if (! is_readable($envPath)) {
            throw new RuntimeException('.env not found. Configure the app on the server first.');
        }

        $env = legacy_load_env($envPath);
        $driver = $env['DB_CONNECTION'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $database = $env['DB_DATABASE'] ?? database_path_default();
            if ($database === ':memory:' || $database === '') {
                $database = database_path_default();
            }
            if (! str_starts_with($database, '/')) {
                $database = dirname(__DIR__, 2).'/'.ltrim($database, '/');
            }
            if (! is_readable($database)) {
                throw new RuntimeException('SQLite database not found at '.$database);
            }

            $pdo = new PDO('sqlite:'.$database);
        } elseif ($driver === 'mysql') {
            $host = $env['DB_HOST'] ?? '127.0.0.1';
            $port = $env['DB_PORT'] ?? '3306';
            $name = $env['DB_DATABASE'] ?? '';
            $user = $env['DB_USERNAME'] ?? '';
            $pass = $env['DB_PASSWORD'] ?? '';
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
        } else {
            throw new RuntimeException('Legacy scripts support sqlite and mysql only.');
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}

if (! function_exists('legacy_load_env')) {
    function legacy_load_env(string $path): array
    {
        $vars = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $vars;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $vars;
    }
}

if (! function_exists('database_path_default')) {
    function database_path_default(): string
    {
        return dirname(__DIR__, 2).'/database/database.sqlite';
    }
}

if (! function_exists('legacy_json_error')) {
    function legacy_json_error(Throwable $e, string $context): void
    {
        error_log($context.': '.$e->getMessage());
        http_response_code(503);
        echo json_encode(['error' => 'Service temporarily unavailable.']);
    }
}
