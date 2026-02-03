<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();
$db = pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_master()) {
    // Create follower
    $account_id = strtoupper(substr(bin2hex(random_bytes(3)),0,6));
    $username = $_POST['username'] ?: 'follower_' . substr(uniqid(), -4);
    $password = substr(bin2hex(random_bytes(4)),0,8);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (username, password_hash, role, account_id, account_name, telegram_id, balance, created_at) VALUES (?, ?, "follower", ?, ?, ?, ?, NOW())');
    $stmt->execute([$username, $password_hash, $account_id, $_POST['account_name'] ?? '', $_POST['telegram'] ?? '', floatval($_POST['balance'] ?? 0)]);
    $id = $db->lastInsertId();
    log_event('account_create', 'Follower account created', ['id' => $id, 'username' => $username]);
    $generated = ['username'=>$username, 'password'=>$password, 'account_id'=>$account_id];
}

$accounts = $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();

render_header('Accounts');
?>
<h2>Accounts</h2>
<div class="panel">
    <div class="panel-actions">
        <?php if (is_master()): ?>
        <form method="post" class="inline-form">
            <input name="account_name" placeholder="Account Name" required>
            <input name="username" placeholder="Username (optional)">
            <input name="telegram" placeholder="Telegram ID (@username)">
            <input name="balance" placeholder="Initial Balance" value="0">
            <button class="btn" type="submit">Add Account</button>
        </form>
        <?php endif; ?>
    </div>
    <table class="list"><thead><tr><th></th><th>Account</th><th>Type</th><th>Lots</th><th>Status</th><th>Balance</th></tr></thead><tbody>
        <?php foreach ($accounts as $a): ?>
            <tr>
                <td><input type="checkbox"></td>
                <td><strong><?php echo htmlspecialchars($a['account_name'] ?: $a['username']); ?></strong><br><small><?php echo htmlspecialchars($a['account_id']); ?></small></td>
                <td><?php echo htmlspecialchars($a['role']); ?></td>
                <td><?php echo htmlspecialchars($a['lots'] ?? '-'); ?></td>
                <td><?php echo $a['status'] ? htmlspecialchars($a['status']) : 'Disconnected'; ?></td>
                <td>$<?php echo number_format($a['balance'] ?? 0,2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table>
</div>

<?php if (!empty($generated)): ?>
<div class="panel notice">
    <h3>New Follower Created</h3>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($generated['username']); ?> <br>
       <strong>Password:</strong> <?php echo htmlspecialchars($generated['password']); ?> <br>
       <strong>Account ID:</strong> <?php echo htmlspecialchars($generated['account_id']); ?></p>
    <p>Please store these credentials safely. Followers will use these to login.</p>
</div>
<?php endif; ?>

<?php render_footer(); ?>