<?php
require_once __DIR__ . '/config.php';

function log_event($type, $message, $meta = null) {
    $db = pdo();
    $stmt = $db->prepare('INSERT INTO logs (type, message, meta, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$type, $message, $meta ? json_encode($meta) : null, date('Y-m-d H:i:s')]);
}

function ensure_tables() {
    $db = pdo();
    try {
        // Quick check if users table exists
        $db->query('SELECT 1 FROM users LIMIT 1');
        return;
    } catch (Exception $e) {
        // Create tables (SQLite/MySQL compatible-ish SQL)
        $db->beginTransaction();
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'follower',
            account_id TEXT,
            account_name TEXT,
            telegram_id TEXT,
            balance REAL DEFAULT 0,
            lots INTEGER,
            status TEXT DEFAULT 'Disconnected',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS risk_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_id INTEGER DEFAULT NULL,
            is_global INTEGER DEFAULT 0,
            lot_multiplier REAL DEFAULT 1.000,
            daily_loss_limit REAL DEFAULT 0,
            max_exposure_per_symbol REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS trades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_id INTEGER NOT NULL,
            symbol TEXT,
            type TEXT DEFAULT 'Buy',
            order_type TEXT DEFAULT 'Market',
            quantity INTEGER DEFAULT 0,
            price REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT,
            message TEXT,
            meta TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->commit();

        // Seed minimal demo data if empty
        $count = $db->query('SELECT COUNT(*) as c FROM users')->fetch()['c'] ?? 0;
        if (!$count) {
            $masterHash = password_hash('password', PASSWORD_DEFAULT);
            $ins = $db->prepare('INSERT INTO users (username,password_hash,role,account_id,account_name,balance,status,created_at) VALUES (?,?,?,?,?,?,?,DATETIME("now"))');
            $ins->execute(['master', $masterHash, 'master', 'ALICEBLUE-1', 'Master Account', 125430.50, 'Connected']);

            $followers = [
                ['john', "John's Growth", 'FG-456', 25000.00, 'Connected'],
                ['sarah', "Sarah's Portfolio", 'HJ-789', 50000.00, 'Disconnected'],
                ['retire', 'Retirement Fund', 'KL-101', 150000.00, 'Disconnected'],
                ['aggressive', 'Aggressive Bets', 'MN-212', 10000.00, 'Connected'],
                ['test', 'Test Account', 'OP-313', 5000.00, 'Error']
            ];
            $ins2 = $db->prepare('INSERT INTO users (username,password_hash,role,account_id,account_name,telegram_id,balance,status,created_at) VALUES (?,?,"follower",?,?,?,?,DATETIME("now"))');
            foreach ($followers as $f) {
                $pw = substr(bin2hex(random_bytes(4)), 0, 8);
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $ins2->execute([$f[0], $hash, $f[2], $f[1], '@'.$f[0], $f[3], $f[4]]);
                log_event('account_create','Seed follower created', ['username'=>$f[0]]);
            }

            // Seed a global risk row
            $db->exec("INSERT INTO risk_settings (is_global, lot_multiplier, daily_loss_limit, max_exposure_per_symbol, created_at) VALUES (1, 1.000, 5000.00, 10000.00, DATETIME('now'))");

            // Sample trades and logs
            $tins = $db->prepare('INSERT INTO trades (account_id, symbol, type, order_type, quantity, price, created_at) VALUES (?,?,?,?,?,?,DATETIME("now"))');
            $tins->execute([2, 'AAPL', 'Buy', 'Limit', 100, 172.25]);
            $tins->execute([3, 'GOOGL', 'Sell', 'Market', 50, 135.50]);
            $tins->execute([4, 'TSLA', 'Buy', 'Stop', 200, 245.00]);

            $db->exec("INSERT INTO logs (type, message, meta, created_at) VALUES ('system', 'Initial seed', NULL, DATETIME('now'))");
        }
    }
}

function compute_account_pl($db, $account_id) {
    // Simple P/L estimate: sells add cash, buys subtract cash
    $stmt = $db->prepare("SELECT SUM(CASE WHEN type = 'Sell' THEN price * quantity ELSE -price * quantity END) as pl FROM trades WHERE account_id = ?");
    $stmt->execute([$account_id]);
    $row = $stmt->fetch();
    return $row && $row['pl'] !== null ? floatval($row['pl']) : 0.0;
}

function get_master_user($db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE role = ? LIMIT 1');
    $stmt->execute(['master']);
    return $stmt->fetch();
}


function render_header($title = 'EquityMirror') {
    $user = current_user();
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body>
    <div class="app">
        <aside class="sidebar">
            <h1 class="logo">MirrorTrade Pro</h1>
            <nav>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/accounts.php">Accounts</a>
                <a href="/risk.php">Risk Management</a>
                <a href="/logs.php">Logs</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/settings.php">Settings</a>
            </div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div class="greet"><?php if ($user) echo 'Hello, ' . htmlspecialchars($user['username']); ?></div>
                <div class="actions">
                    <?php if ($user): ?>
                        <a href="/auth.php?action=logout">Logout</a>
                    <?php endif; ?>
                </div>
            </header>
            <section class="content">
    <?php
}

function render_footer() {
    ?>
            </section>
        </main>
    </div>
    <script src="/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
?>