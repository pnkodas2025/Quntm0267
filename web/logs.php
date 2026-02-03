<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();
$db = pdo();
$logs = $db->query('SELECT * FROM logs ORDER BY created_at DESC LIMIT 200')->fetchAll();

render_header('Logs');
?>
<h2>Logs</h2>
<div class="panel">
    <table class="simple"><thead><tr><th>Time</th><th>Type</th><th>Message</th><th>Meta</th></tr></thead><tbody>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td><?php echo htmlspecialchars($l['created_at']); ?></td>
            <td><?php echo htmlspecialchars($l['type']); ?></td>
            <td><?php echo htmlspecialchars($l['message']); ?></td>
            <td><pre><?php echo htmlspecialchars($l['meta']); ?></pre></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>

<?php render_footer(); ?>