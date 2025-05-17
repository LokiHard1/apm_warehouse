<?php
require 'config.php';
checkAdminAccess();

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header('Location: dashboard.php');
    exit;
}

$order_id = (int)$_GET['id'];
$status = $_GET['status'];

$allowed_statuses = ['processing', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['error_message'] = 'Недопустимый статус заказа';
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT o.*, i.product_name, i.quantity as current_quantity FROM orders o JOIN inventory i ON o.product_id = i.id WHERE o.id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $_SESSION['error_message'] = 'Заказ не найден';
        header('Location: dashboard.php');
        exit;
    }
    
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $order_id]);
    
    if ($status === 'completed') {
        $new_quantity = $order['current_quantity'] - $order['quantity'];
        if ($new_quantity < 0) {
            $_SESSION['error_message'] = 'Недостаточно товара на складе для выполнения заказа';
            header('Location: dashboard.php');
            exit;
        }
        
        $stmt = $pdo->prepare('UPDATE inventory SET quantity = ? WHERE id = ?');
        $stmt->execute([$new_quantity, $order['product_id']]);
    }
    
    $status_names = [
        'processing' => 'в обработку',
        'completed' => 'завершен',
        'cancelled' => 'отменен'
    ];
    
    logActivity($pdo, 'update_order', 'Заказ #' . $order_id . ' (' . $order['product_name'] . ') переведен в статус: ' . $status_names[$status]);
    
    $_SESSION['success_message'] = 'Статус заказа успешно обновлен';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка при обновлении статуса заказа: ' . $e->getMessage();
}

header('Location: dashboard.php');
exit;
?>