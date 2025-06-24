<?php
function sendNotification($conn, $recipientId, $recipientType, $title, $message, $entityType, $entityId) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            client_id,
            employee_id,
            title,
            message,
            related_entity_type,
            related_entity_id,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    )");
    if ($stmt === false) {
        error_log("Ошибка подготовки уведомления: " . $conn->error);
        return false;
    }
    $clientId = $recipientType === 'client' ? $recipientId : null;
    $employeeId = $recipientType === 'employee' ? $recipientId : null;
    $stmt->bind_param("isssi", $clientId, $employeeId, $title, $message, $entityType, $entityId);
    $result = $stmt->execute();
    if (!$result) {
        error_log("Ошибка выполнения уведомления: " . $stmt->error);
    }
    $stmt->close();
    return $result;
}
?>