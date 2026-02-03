<?php
// db.php — small DB helper + local-only health-check endpoint
// Usage: include 'db.php'; $pdo = db();
require_once __DIR__ . '/config.php';

function db() {
    return pdo();
}

function db_test() {
    try {
        $pdo = db();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        // Try a safe read
        $stmt = $pdo->query("SELECT 1");
        $ok = $stmt && $stmt->fetchColumn() !== false;
        return [
            'ok' => $ok,
            'driver' => $driver,
            'message' => $ok ? 'Connection and basic query succeeded' : 'Query failed',
        ];
    } catch (Exception $e) {
        return [
            'ok' => false,
            'driver' => isset($pdo) ? $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : null,
            'message' => $e->getMessage()
        ];
    }
}

// Local-only HTTP endpoint: ?action=test
if (php_sapi_name() !== 'cli' && isset($_GET['action']) && $_GET['action'] === 'test') {
    // Limit access to localhost to avoid exposing DB details
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!in_array($remote, ['127.0.0.1', '::1'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Forbidden - local access only', 'remote' => $remote]);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(db_test());
    exit;
}

// Example convenience: get DB summary without exposing credentials
function db_summary() {
    $dsn = sprintf('%s:%s', PDO::getAvailableDrivers()[0] ?? 'pdo', defined('DB_HOST') ? DB_HOST : '');
    return [
        'host' => defined('DB_HOST') ? DB_HOST : null,
        'name' => defined('DB_NAME') ? DB_NAME : null,
        'user' => defined('DB_USER') ? DB_USER : null,
        'driver_candidates' => PDO::getAvailableDrivers()
    ];
}

?>