<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $db = pdo();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        log_event('auth', 'User logged in', ['user_id' => $user['id']]);
        header('Location: /dashboard.php');
        exit;
    } else {
        $message = 'Invalid username or password';
        log_event('auth_fail', 'Failed login attempt', ['username' => $username]);
    }
}
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - EquityMirror</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth">
<div class="auth-card">
    <h2>EquityMirror — Login</h2>
    <?php if ($message): ?><div class="alert error"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="post">
        <label>Username<input name="username" required></label>
        <label>Password<input name="password" type="password" required></label>
        <button type="submit" class="btn">Sign In</button>
    </form>
    <p class="muted">For demo: master / password. Followers are created by Master.</p>
</div>
</body>
</html>