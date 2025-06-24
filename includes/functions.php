<?php
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function isEmployee() {
    return isset($_SESSION['employee_id']);
}

function isClient() {
    return isset($_SESSION['client_id']) && !isset($_SESSION['employee_id']);
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

function redirectIfNotEmployee() {
    if (!isEmployee()) {
        header('Location: login.php');
        exit;
    }
}

function redirectIfNotClient() {
    if (!isClient()) {
        header('Location: login.php');
        exit;
    }
}

function getDepartment() {
    return $_SESSION['department'] ?? null;
}

function logAction($action, $details = '') {
    global $conn;
    $userId = $_SESSION['employee_id'] ?? $_SESSION['client_id'] ?? 0;
    $userType = isAdmin() ? 'admin' : (isEmployee() ? 'employee' : 'client');
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, user_type, action, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $userType, $action, $details, $ip);
    $stmt->execute();
}
?>