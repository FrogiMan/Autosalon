<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $term = intval($_POST['term']);
    $rate = floatval($_POST['rate']);

    if ($amount <= 0 || $term <= 0 || $rate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
        exit;
    }

    // Расчет аннуитетного платежа
    $monthlyRate = $rate / 100 / 12;
    $monthlyPayment = $amount * ($monthlyRate / (1 - pow(1 + $monthlyRate, -$term)));
    $totalPayment = $monthlyPayment * $term;

    echo json_encode([
        'success' => true,
        'monthlyPayment' => $monthlyPayment,
        'totalPayment' => $totalPayment
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Недопустимый метод']);
}
?>