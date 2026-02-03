<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();
if (!is_master()) {
    header('Location: /dashboard.php'); exit;
}
$db = pdo();

// Global settings row id = 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lot_multiplier = floatval($_POST['lot_multiplier'] ?? 1);
    $daily_loss = floatval($_POST['daily_loss'] ?? 0);
    $max_exposure = floatval($_POST['max_exposure'] ?? 0);
    $stmt = $db->prepare('INSERT INTO risk_settings (is_global, lot_multiplier, daily_loss_limit, max_exposure_per_symbol, created_at) VALUES (1, ?, ?, ?, NOW())');
    $stmt->execute([$lot_multiplier, $daily_loss, $max_exposure]);
    log_event('risk_update', 'Global risk updated', ['lot_multiplier'=>$lot_multiplier]);
}

$global = $db->query('SELECT * FROM risk_settings WHERE is_global=1 ORDER BY id DESC LIMIT 1')->fetch();

render_header('Risk Management');
?>
<h2>Risk Management</h2>
<div class="panel">
    <h3>Global Risk Settings</h3>
    <form method="post" class="simple-form">
        <label>Lot Multiplier<input name="lot_multiplier" value="<?php echo htmlspecialchars($global['lot_multiplier'] ?? 1); ?>"></label>
        <label>Daily Loss Limit ($)<input name="daily_loss" value="<?php echo htmlspecialchars($global['daily_loss_limit'] ?? 5000); ?>"></label>
        <label>Max Exposure per Symbol ($)<input name="max_exposure" value="<?php echo htmlspecialchars($global['max_exposure_per_symbol'] ?? 10000); ?>"></label>
        <button class="btn" type="submit">Save</button>
    </form>
</div>

<div class="panel">
    <h3>Per Account Settings</h3>
    <table class="simple"><thead><tr><th>Account</th><th>Lot Mult</th><th>Daily Loss</th><th>Max Exposure</th></tr></thead><tbody>
    <?php
    $accounts = $db->query('SELECT id, username, account_id FROM users WHERE role = "follower"')->fetchAll();
    foreach ($accounts as $a) {
        $r = $db->prepare('SELECT * FROM risk_settings WHERE account_id = ? ORDER BY id DESC LIMIT 1');
        $r->execute([$a['id']]);
        $rs = $r->fetch();
        echo '<tr><td>'.htmlspecialchars($a['username'] . ' (' . $a['account_id'] . ')').'</td><td>'.htmlspecialchars($rs['lot_multiplier'] ?? '—').'</td><td>'.htmlspecialchars($rs['daily_loss_limit'] ?? '—').'</td><td>'.htmlspecialchars($rs['max_exposure_per_symbol'] ?? '—').'</td></tr>';
    }
    ?>
    </tbody></table>
</div>

<?php render_footer(); ?>