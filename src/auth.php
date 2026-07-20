<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return fetchOne("SELECT id, username, email, full_name, role FROM users WHERE id = ?", [$_SESSION['admin_id']]);
}

function login($username, $password) {
    $user = fetchOne("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1", [$username, $username]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}
