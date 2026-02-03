<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();
$user = current_user();
$db = pdo();

// Ensure local tables exist when using SQLite fallback
if (function_exists('ensure_tables')) ensure_tables();

// Handle trade creation (master or follower)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_trade' && is_master()) {
        $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
        $type = ($_POST['type'] ?? 'Buy');
        $qty = intval($_POST['quantity'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        if ($symbol && $qty > 0) {
            $stmt = $db->prepare('INSERT INTO trades (account_id, symbol, type, order_type, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, ?, DATETIME("now"))');
            $stmt->execute([$user['id'], $symbol, $type, $_POST['order_type'] ?? 'Market', $qty, $price]);
            log_event('trade_create', 'Master created trade', ['symbol'=>$symbol,'qty'=>$qty,'price'=>$price]);
            header('Location: /dashboard.php'); exit;
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'log_trade' && !$is_master = is_master()) {
        // follower logging a trade manually
        $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
        $type = ($_POST['type'] ?? 'Buy');
        $qty = intval($_POST['quantity'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        if ($symbol && $qty > 0) {
            $stmt = $db->prepare('INSERT INTO trades (account_id, symbol, type, order_type, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, ?, DATETIME("now"))');
            $stmt->execute([$user['id'], $symbol, $type, $_POST['order_type'] ?? 'Market', $qty, $price]);
            log_event('trade_log', 'Follower logged trade', ['username'=>$user['username'],'symbol'=>$symbol,'qty'=>$qty]);
            header('Location: /dashboard.php'); exit;
        }
    }
}

// Stats
// total P/L for followers (sum of computed PLs)
$followersList = $db->query('SELECT id, username, account_id, balance, status FROM users WHERE role = "follower"')->fetchAll();
$total_pl = 0.0;
foreach ($followersList as $f) { $total_pl += compute_account_pl($db, $f['id']); }

render_header('Dashboard');
?>
<h2>Dashboard</h2>
<div class="cards">
    <div class="card">Total Followers P/L<br><strong>$<?php echo number_format($total_pl,2); ?></strong></div>
    <div class="card">Follower Accounts<br><strong><?php echo count($followersList); ?></strong></div>
    <div class="card">Recent Trades<br><strong><?php echo $db->query('SELECT COUNT(*) FROM trades')->fetchColumn(); ?></strong></div>
    <div class="card">You are<br><strong><?php echo htmlspecialchars($user['username']).' ('.htmlspecialchars($user['role']).')'; ?></strong></div>
</div>

<?php if (is_master()): ?>
    <div class="panel">
        <h3>Followers</h3>
        <table class="list"><thead><tr><th>Account</th><th>Status</th><th>Balance</th><th>P/L</th></tr></thead><tbody>
            <?php foreach ($followersList as $f): $pl = compute_account_pl($db, $f['id']); ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($f['username']); ?></strong><br><small><?php echo htmlspecialchars($f['account_id']); ?></small></td>
                    <td><?php echo htmlspecialchars($f['status']); ?></td>
                    <td>$<?php echo number_format($f['balance'],2); ?></td>
                    <td>$<?php echo number_format($pl,2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    </div>

    <div class="panel">
        <h3>Create Trade (Master)</h3>
        <form method="post" class="simple-form">
            <input type="hidden" name="action" value="create_trade">
            <label>Symbol<input name="symbol" required></label>
            <label>Type<select name="type"><option>Buy</option><option>Sell</option></select></label>
            <label>Order Type<input name="order_type" value="Market"></label>
            <label>Quantity<input name="quantity" type="number" value="100"></label>
            <label>Price<input name="price" type="number" step="0.01" value="0"></label>
            <button class="btn" type="submit">Create Trade</button>
        </form>
    </div>
<?php else: ?>
    <div class="panel">
        <h3>Your Account</h3>
        <?php
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1'); $stmt->execute([$user['id']]); $me = $stmt->fetch();
        $mypl = compute_account_pl($db, $me['id']);
        ?>
        <p><strong><?php echo htmlspecialchars($me['account_name'] ?: $me['username']); ?></strong></p>
        <p>Balance: $<?php echo number_format($me['balance'],2); ?> — P/L: $<?php echo number_format($mypl,2); ?></p>
    </div>

    <div class="panel">
        <h3>Master Trade Feed</h3>
        <?php $master = get_master_user($db); if ($master): $feed = $db->prepare('SELECT * FROM trades WHERE account_id = ? ORDER BY created_at DESC LIMIT 20'); $feed->execute([$master['id']]); $feed = $feed->fetchAll(); ?>
            <table class="simple"><thead><tr><th>Time</th><th>Symbol</th><th>Type</th><th>Qty</th><th>Price</th></tr></thead><tbody>
            <?php foreach ($feed as $f): ?>
                <tr><td><?php echo htmlspecialchars($f['created_at']); ?></td><td><?php echo htmlspecialchars($f['symbol']); ?></td><td><?php echo htmlspecialchars($f['type']); ?></td><td><?php echo htmlspecialchars($f['quantity']); ?></td><td>$<?php echo number_format($f['price'],2); ?></td></tr>
            <?php endforeach; ?></tbody></table>
        <?php else: ?>
            <p>No master configured.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Log Trade (Manual)</h3>
        <form method="post" class="simple-form">
            <input type="hidden" name="action" value="log_trade">
            <label>Symbol<input name="symbol" required></label>
            <label>Type<select name="type"><option>Buy</option><option>Sell</option></select></label>
            <label>Order Type<input name="order_type" value="Market"></label>
            <label>Quantity<input name="quantity" type="number" value="100"></label>
            <label>Price<input name="price" type="number" step="0.01" value="0"></label>
            <button class="btn" type="submit">Log Trade</button>
        </form>
    </div>
<?php endif; ?>

<div class="panel">
    <h3>Recent Trades (All)</h3>
    <table class="simple"><thead><tr><th>Time</th><th>Symbol</th><th>Type</th><th>Qty</th><th>Price</th><th>Account</th></tr></thead><tbody>
    <?php
    $trades = $db->query('SELECT t.*, u.username FROM trades t LEFT JOIN users u ON u.id = t.account_id ORDER BY t.created_at DESC LIMIT 20')->fetchAll();
    foreach ($trades as $t) {
        echo '<tr><td>'.htmlspecialchars($t['created_at']).'</td><td>'.htmlspecialchars($t['symbol']).'</td><td>'.htmlspecialchars($t['type']).'</td><td>'.htmlspecialchars($t['quantity']).'</td><td>$'.number_format($t['price'],2).'</td><td>'.htmlspecialchars($t['username']).'</td></tr>';
    }
    ?>
    </tbody></table>
</div>

<?php render_footer(); ?>