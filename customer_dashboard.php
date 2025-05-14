<?php
require 'config.php';
checkCustomerAccess();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stmt = $pdo->query('SELECT * FROM inventory WHERE quantity > 0 ORDER BY product_name');
$products = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT o.*, i.product_name FROM orders o JOIN inventory i ON o.product_id = i.id WHERE o.customer_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    try {
        $stmt = $pdo->prepare('SELECT quantity FROM inventory WHERE id = ?');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product || $product['quantity'] < $quantity) {
            $_SESSION['error_message'] = 'Недостаточно товара на складе';
        } else {
            $stmt = $pdo->prepare('INSERT INTO orders (customer_id, product_id, quantity) VALUES (?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            
            logActivity($pdo, 'place_order', 'Заказ создан: товар ID ' . $product_id . ', количество: ' . $quantity);
            
            $_SESSION['success_message'] = 'Заказ успешно создан';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Ошибка при создании заказа: ' . $e->getMessage();
    }
    
    header('Location: customer_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APM Склад - Панель заказчика</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>APM Склад - Панель заказчика</h1>
        <div class="user-info">
            Вы вошли как: <?= htmlspecialchars($_SESSION['full_name']) ?>
            <a href="logout.php" class="logout">Выйти</a>
        </div>
    </header>
    
    <nav class="main-nav">
        <a href="customer_dashboard.php" class="active">Товары</a>
        <a href="#orders">Мои заказы</a>
    </nav>
    
    <main>
        <?php if ($success_message): ?>
            <div class="success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?= $error_message ?></div>
        <?php endif; ?>
        
        <section id="products">
            <h2>Доступные товары</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Количество</th>
                        <th>Место хранения</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td><?= $product['quantity'] ?></td>
                        <td><?= htmlspecialchars($product['location']) ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>">
                                <button type="submit" name="place_order" class="btn-small">Заказать</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <section id="orders">
    <h2>Мои заказы</h2>
    
    <table>
        <thead>
            <tr>
                <th>Товар</th>
                <th>Количество</th>
                <th>Статус</th>
                <th>Дата заказа</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['product_name']) ?></td>
                <td><?= $order['quantity'] ?></td>
                <td class="status-<?= $order['status'] ?>">
                    <?php 
                    $statuses = [
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен'
                    ];
                    echo $statuses[$order['status']] ?? $order['status'];
                    ?>
                </td>
                <td><?= $order['created_at'] ?></td>
                <td>
                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn-small btn-danger" onclick="return confirm('Вы уверены, что хотите отменить этот заказ?')">Отменить</a>
                    <?php else: ?>
                        <span>Нет действий</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
    </main>
</body>
</html>