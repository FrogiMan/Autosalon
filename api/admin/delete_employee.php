<?php
// api/admin/delete_employee.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $employeeId = intval($_DELETE['employee_id']);

    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'ID сотрудника не указан']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Проверка, что сотрудник не является последним администратором
        $adminCheckStmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM employees WHERE is_admin = 1");
        $adminCheckStmt->execute();
        $adminCount = $adminCheckStmt->get_result()->fetch_assoc()['admin_count'];
        $isAdminStmt = $conn->prepare("SELECT is_admin FROM employees WHERE employee_id = ?");
        $isAdminStmt->bind_param("i", $employeeId);
        $isAdminStmt->execute();
        $isAdmin = $isAdminStmt->get_result()->fetch_assoc()['is_admin'];

        if ($isAdmin && $adminCount <= 1) {
            throw new Exception('Нельзя удалить последнего администратора');
        }

        // Удаление фото сотрудника
        $photoStmt = $conn->prepare("SELECT photo FROM employees WHERE employee_id = ?");
        $photoStmt->bind_param("i", $employeeId);
        $photoStmt->execute();
        $photo = $photoStmt->get_result()->fetch_assoc()['photo'];
        if ($photo && $photo !== 'default.jpg' && file_exists('../../images/employees/' . $photo)) {
            unlink('../../images/employees/' . $photo);
        }

        // Обновление связанных записей
        $conn->query("UPDATE sales_department SET employee_id = NULL WHERE employee_id = $employeeId");
        $conn->query("UPDATE test_drive_requests SET employee_id = NULL WHERE employee_id = $employeeId");
        $conn->query("UPDATE service_requests SET employee_id = NULL WHERE employee_id = $employeeId");

        // Удаление сотрудника
        $deleteStmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $deleteStmt->bind_param("i", $employeeId);
        if (!$deleteStmt->execute()) {
            throw new Exception('Ошибка при удалении сотрудника');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Сотрудник успешно удален']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in delete_employee: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>