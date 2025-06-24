<?php
session_start();
require_once '../includes/db.php';


header('Content-Type: application/json');

$response = ['success' => false, 'notifications' => []];

if (isset($_SESSION['client_id'])) {
    $userId = $_SESSION['client_id'];
    $userType = 'client';
} elseif (isset($_SESSION['employee_id'])) {
    $userId = $_SESSION['employee_id'];
    $userType = 'employee';
} else {
    $response['message'] = 'Необходима авторизация';
    echo json_encode($response);
    exit;
}

try {
    if ($userType === 'client') {
        $query = "SELECT n.*, 
                 CASE 
                     WHEN n.related_entity_type = 'test_drive' THEN t.preferred_date
                     WHEN n.related_entity_type = 'service' THEN s.preferred_date
                     WHEN n.related_entity_type = 'order' THEN o.order_date
                     ELSE NULL
                 END AS related_date
                 FROM notifications n
                 LEFT JOIN test_drive_requests t ON n.related_entity_type = 'test_drive' AND n.related_entity_id = t.request_id
                 LEFT JOIN service_requests s ON n.related_entity_type = 'service' AND n.related_entity_id = s.request_id
                 LEFT JOIN orders o ON n.related_entity_type = 'order' AND n.related_entity_id = o.order_id
                 WHERE n.client_id = ?
                 ORDER BY n.created_at DESC
                 LIMIT 50";
    } else {
        $query = "SELECT n.*, 
                 CASE 
                     WHEN n.related_entity_type = 'test_drive' THEN t.preferred_date
                     WHEN n.related_entity_type = 'service' THEN s.preferred_date
                     WHEN n.related_entity_type = 'order' THEN o.order_date
                     ELSE NULL
                 END AS related_date
                 FROM notifications n
                 LEFT JOIN test_drive_requests t ON n.related_entity_type = 'test_drive' AND n.related_entity_id = t.request_id
                 LEFT JOIN service_requests s ON n.related_entity_type = 'service' AND n.related_entity_id = s.request_id
                 LEFT JOIN orders o ON n.related_entity_type = 'order' AND n.related_entity_id = o.order_id
                 WHERE n.employee_id = ?
                 ORDER BY n.created_at DESC
                 LIMIT 50";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $response['success'] = true;
    $response['notifications'] = $notifications;
    
    // Mark notifications as read
    if (!empty($notifications)) {
        $unreadIds = array_column(array_filter($notifications, function($n) { 
            return !$n['is_read']; 
        }), 'notification_id');
        
        if (!empty($unreadIds)) {
            $placeholders = implode(',', array_fill(0, count($unreadIds), '?'));
            $types = str_repeat('i', count($unreadIds));
            
            $update = $conn->prepare("UPDATE notifications SET is_read = TRUE 
                                    WHERE notification_id IN ($placeholders)");
            $update->bind_param($types, ...$unreadIds);
            $update->execute();
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Ошибка при получении уведомлений: ' . $e->getMessage();
}

echo json_encode($response);
?>