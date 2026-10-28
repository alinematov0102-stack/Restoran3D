<?php
// Production configuration. Never commit .env to Git.
function ff_env(string $key, ?string $default = null): ?string {
    static $env = null;
    if ($env === null) {
        $env = [];
        $file = __DIR__ . '/.env';
        if (is_file($file) && is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $k = trim($parts[0]); $v = trim($parts[1]);
                    if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) $v = substr($v, 1, -1);
                    $env[$k] = $v;
                }
            }
        }
    }
    return $env[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
}

define('DB_HOST', ff_env('DB_HOST', 'localhost'));
define('DB_PORT', ff_env('DB_PORT', '3306'));
define('DB_SSL', ff_env('DB_SSL', '0'));
define('DB_NAME', ff_env('DB_NAME', 'foodadmin'));
define('DB_USER', ff_env('DB_USER', 'root'));
define('DB_PASS', ff_env('DB_PASS', ''));
define('APP_NAME', ff_env('APP_NAME', 'FastFood'));
define('APP_ENV', ff_env('APP_ENV', 'production'));
