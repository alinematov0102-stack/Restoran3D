<?php
require_once __DIR__ . '/config.php';
try {
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true,   // har so'rovda SSL ulanishni qayta o'rnatmaslik uchun
        PDO::ATTR_TIMEOUT => 10,
    ];
    if (DB_SSL === '1') {
        // TiDB Cloud / Aiven kabi bulutli bazalar SSL talab qiladi
        $opts[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS, $opts
    );
} catch (PDOException $e) {
    error_log('FastFood DB connection error: ' . $e->getMessage());
    http_response_code(500);
    exit(APP_ENV === 'production' ? 'Server configuration error.' : 'Bazaga ulanishda xatolik: ' . htmlspecialchars($e->getMessage()));
}
