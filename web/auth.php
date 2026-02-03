<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

action:
$action = $_GET['action'] ?? 'logout';
if ($action === 'logout') {
    session_destroy();
    header('Location: /index.php');
    exit;
}
?>