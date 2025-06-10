<?php
require_once __DIR__ . '/../config/config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function login($email, $password) {
    $db = new Database();
    $conn = $db->connect();

    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT * FROM clients WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['client_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            return true;
        }
    }
    $db->close();
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>