<?php
require 'config.php';
checkAdminAccess();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$product_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare('DELETE FROM inventory WHERE id = ?');
    $stmt->execute([$product_id]);
    
    logActivity($pdo, 'delete_product', 'Удален товар ID: ' . $product_id);

    $_SESSION['success_message'] = 'Товар успешно удален';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Ошибка при удалении товара: ' . $e->getMessage();
}

header('Location: dashboard.php');
exit;
?>