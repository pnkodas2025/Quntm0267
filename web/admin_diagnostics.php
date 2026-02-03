<?php
// admin_diagnostics.php
// Usage: https://yourdomain/admin_diagnostics.php?token=YOUR_TOKEN
// Configure token in web/db_credentials.php by setting ADMIN_TOKEN constant.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';
if (!defined('ADMIN_TOKEN') || ADMIN_TOKEN === 'change_me_now') {
    http_response_code(403);
    echo "Admin diagnostics disabled. Please set ADMIN_TOKEN in web/db_credentials.php and use a strong secret.\n";
    exit;
}

if (!$token || !hash_equals(ADMIN_TOKEN, $token)) {
    http_response_code(403);
    echo "Forbidden. Invalid token.\n";
    exit;
}

header('Content-Type: text/plain');
echo "EquityMirror Admin Diagnostics\n";
echo "===========================\n\n";

// PHP info
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded extensions: " . implode(', ', get_loaded_extensions()) . "\n";
echo "PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

// Config and environment
echo "DB host: " . (defined('DB_HOST') ? DB_HOST : '(not defined)') . "\n";
echo "DB name: " . (defined('DB_NAME') ? DB_NAME : '(not defined)') . "\n";
echo "DB user: " . (defined('DB_USER') ? DB_USER : '(not defined)') . "\n\n";

// DB test
$test = db_test();
echo "DB test: " . json_encode($test) . "\n\n";

// Check required PDO MySQL
$hasPDO = extension_loaded('pdo');
$hasPDO_mysql = extension_loaded('pdo_mysql');
echo "extension pdo: " . ($hasPDO ? 'yes' : 'no') . "\n";
echo "extension pdo_mysql: " . ($hasPDO_mysql ? 'yes' : 'no') . "\n\n";

// Error log
$error_log = ini_get('error_log');
echo "php error_log: " . ($error_log ? $error_log : '(not set)') . "\n";
if ($error_log && is_readable($error_log)) {
    echo "\n--- tail of error log ---\n";
    $lines = array_slice(explode("\n", file_get_contents($error_log)), -60);
    echo implode("\n", $lines) . "\n";
} else {
    echo "(error log not readable or not set)\n";
}

// File permissions for key files
$files = ['/config.php','/db_credentials.php','/db.php','/index.php'];
foreach ($files as $f) {
    $p = __DIR__ . $f;
    echo "\n" . basename($f) . ": ";
    if (file_exists($p)) {
        echo sprintf("exists, perms=%o", fileperms($p) & 0777);
    } else echo "missing";
}

echo "\n\nEnd of diagnostics.\n";
exit;
?>