<?php
require 'config.php';
checkCustomerAccess();

if (!isset($_GET['id'])) {
    header('Location: customer_dashboard.php');
    exit;
}

$order_id = (int)$_GET['id'];

$stmt = $pdo->prepare('SELECT o.*, i.product_name, i.quantity as current_quantity FROM orders o JOIN inventory i ON o.product_id = i.id WHERE o.id = ? AND o.customer_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = 'Заказ не найден или вам не принадлежит';
    header('Location: customer_dashboard.php');
    exit;
}

try {
    // Если заказ был в обработке, возвращаем товар на склад
    if ($order['status'] === 'processing') {
        $new_quantity = $order['current_quantity'] + $order['quantity'];
        $stmt = $pdo->prepare('UPDATE inventory SET quantity = ? WHERE id = ?');
        $stmt->execute([$new_quantity, $order['product_id']]);
    }
    
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute(['cancelled', $order_id]);
    
    logActivity($pdo, 'cancel_order', 'Заказ #' . $order_id . ' отменен пользователем');
    
    $_SESSION['success_message'] = 'Заказ успешно отменен';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка при отмене заказа: ' . $e->getMessage();
}

header('Location: customer_dashboard.php');
exit;
?>