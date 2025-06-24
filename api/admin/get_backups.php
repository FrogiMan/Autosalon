<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
header('Content-Type: application/json');
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}
$backupDir = '../../backups/';
$backups = array_diff(scandir($backupDir), ['.', '..']);
echo json_encode(['success' => true, 'backups' => array_values($backups)]);
$conn->close();
?>