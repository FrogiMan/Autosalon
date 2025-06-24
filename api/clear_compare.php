<?php
session_start();
header('Content-Type: application/json');

unset($_SESSION['compare']);
echo json_encode(['success' => true]);
?>