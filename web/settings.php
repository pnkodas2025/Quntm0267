<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();

render_header('Settings');
?>
<h2>Settings</h2>
<div class="panel">
    <p>Simple static settings page for demo.</p>
    <p>Change `config.php` to add your database credentials before uploading to Hostinger.</p>
</div>

<?php render_footer(); ?>