<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() { return !empty($_SESSION['user_id']); }
function user_role() { return $_SESSION['user_role'] ?? ''; }
function user_name() { return $_SESSION['user_name'] ?? ''; }

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (user_role() !== 'admin') {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    if ($sessionToken === null) return false; // no token in session = reject
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && $t !== '' && hash_equals($sessionToken, $t);
}
