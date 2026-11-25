<?php
require __DIR__ . '/inc/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: index.php');
