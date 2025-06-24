<?php

function processPayment($cardNumber, $amount, $cardholder, $cardExpiry, $cardCvc) {
    // Здесь должна быть интеграция с реальной платёжной системой (Stripe, PayPal и т.д.)
    // Для примера возвращаем успешный результат
    if ($amount > 0 && !empty($cardNumber) && !empty($cardholder)) {
        return ['success' => true, 'transaction_id' => 'TX' . rand(1000, 9999)];
    }
    return ['success' => false, 'message' => 'Ошибка обработки платежа'];
}
?>