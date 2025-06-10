<?php
session_start();
session_unset();
session_destroy();
$_SESSION['message'] = 'Вы успешно вышли из системы.';
header('Location: ../../login.php');
exit;
?>