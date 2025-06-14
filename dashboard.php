<?php
require 'config.php';
checkAdminAccess();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stmt = $pdo->query('SELECT * FROM inventory ORDER BY last_updated DESC');
$inventory = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $quantity = (int)$_POST['quantity'];
    $location = $_POST['location'];
    
    $stmt = $pdo->prepare('INSERT INTO inventory (product_name, quantity, location) VALUES (?, ?, ?)');
    $stmt->execute([$product_name, $quantity, $location]);
    
    logActivity($pdo, 'add_product', 'Добавлен товар: ' . $product_name);

    header('Location: dashboard.php');
    exit;
}
$topProductsQuery = $pdo->query('
    SELECT 
        i.product_name,
        COUNT(DISTINCT o.customer_id) as unique_customers,
        SUM(o.quantity) as total_ordered
    FROM orders o
    JOIN inventory i ON o.product_id = i.id
    WHERE o.status != "cancelled"
    GROUP BY i.product_name
    ORDER BY unique_customers DESC, total_ordered DESC
    LIMIT 5
');

$topProducts = $topProductsQuery->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $id = (int)$_POST['id'];
    $new_quantity = (int)$_POST['new_quantity'];
    
    $stmt = $pdo->prepare('UPDATE inventory SET quantity = ? WHERE id = ?');
    $stmt->execute([$new_quantity, $id]);
    
    logActivity($pdo, 'update_product', 'Обновлен товар ID: ' . $id . ' (новое количество: ' . $new_quantity . ')');

    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APM Склад - Панель управления</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
        <header>
        <h1>APM Склад - <?= $page_title ?? 'Панель управления' ?></h1>
        <div class="user-info">
            Вы вошли как: <?= htmlspecialchars($_SESSION['full_name']) ?>
            <a href="logout.php" class="logout">Выйти</a>
        </div>
    </header>
    
    <nav class="main-nav">
    <a href="dashboard.php" <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'class="active"' : '' ?>>Панель управления</a>
    <a href="reports.php" <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'class="active"' : '' ?>>История действий</a>
    <a href="export_logs.php" <?= basename($_SERVER['PHP_SELF']) === 'export_logs.php' ? 'class="active"' : '' ?>>Экспорт истории</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="register.php" <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'class="active"' : '' ?>>Добавить пользователя</a>
    <?php endif; ?>
    </nav>
    
    <main>
        <section class="add-product">
            <h2>Добавить новый товар</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="product_name">Название товара:</label>
                    <input type="text" id="product_name" name="product_name" required>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Количество:</label>
                    <input type="number" id="quantity" name="quantity" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="location">Место хранения:</label>
                    <input type="text" id="location" name="location" required>
                </div>
                
                <button type="submit" name="add_product" class="btn">Добавить</button>
            </form>
        </section>
        
        <section class="inventory-list">
            <h2>Текущие запасы</h2>

            <?php if ($success_message): ?>
    <div class="success"><?= $success_message ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="error"><?= $error_message ?></div>
<?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Количество</th>
                        <th>Место</th>
                        <th>Последнее обновление</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <input type="number" name="new_quantity" value="<?= $item['quantity'] ?>" min="0">
                                <button type="submit" name="update_quantity" class="btn-small">Обновить</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($item['location']) ?></td>
                        <td><?= $item['last_updated'] ?></td>
                        <td>
                            <a href="delete_product.php?id=<?= $item['id'] ?>" class="btn-small btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <section class="orders-management">
    <h2>Управление заказами</h2>
    
    <?php
    $stmt = $pdo->query('
        SELECT o.*, i.product_name, u.full_name AS customer_name 
        FROM orders o
        JOIN inventory i ON o.product_id = i.id
        JOIN users u ON o.customer_id = u.id
        ORDER BY o.created_at DESC
    ');
    $orders = $stmt->fetchAll();
    ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Товар</th>
                <th>Количество</th>
                <th>Заказчик</th>
                <th>Дата заказа</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['product_name']) ?></td>
                <td><?= $order['quantity'] ?></td>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                <td><?= $order['created_at'] ?></td>
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
                <td>
                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="update_order.php?id=<?= $order['id'] ?>&status=processing" class="btn-small">В обработку</a>
                        <a href="update_order.php?id=<?= $order['id'] ?>&status=cancelled" class="btn-small btn-danger">Отменить</a>
                    <?php elseif ($order['status'] === 'processing'): ?>
                        <a href="update_order.php?id=<?= $order['id'] ?>&status=completed" class="btn-small">Завершить</a>
                        <a href="update_order.php?id=<?= $order['id'] ?>&status=cancelled" class="btn-small btn-danger">Отменить</a>
                    <?php else: ?>
                        <span>Нет действий</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="top-products-section" id="top-products">
    <h2>Топ популярных продуктов</h2>
    <p>Рейтинг основан на количестве уникальных заказчиков и общем количестве заказов</p>
    
    <?php if (!empty($topProducts)): ?>
        <table class="top-products-table">
            <thead>
                <tr>
                    <th>Место</th>
                    <th>Название продукта</th>
                    <th>Уникальных заказчиков</th>
                    <th>Всего заказано</th>
                    <th>Рейтинг популярности</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $index => $product): 
                    // Рассчитываем рейтинг (60% вес - уникальные заказчики, 40% - общее количество)
                    $rating = 0.6 * $product['unique_customers'] + 0.4 * ($product['total_ordered'] / 10);
                ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td><?= $product['unique_customers'] ?></td>
                        <td><?= $product['total_ordered'] ?></td>
                        <td>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?= min(100, $rating * 10) ?>%"></div>
                                <span class="rating-value"><?= number_format($rating, 1) ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="popularity-analysis">
            <h3>Анализ популярности</h3>
            <p>Рейтинг рассчитывается по формуле: <strong>0.6 × (уникальные заказчики) + 0.4 × (всего заказано / 10)</strong></p>
            <p>Это позволяет учитывать как широту спроса (количество разных заказчиков), так и глубину (общее количество заказов).</p>
        </div>
    <?php else: ?>
        <p>Нет данных о заказах для составления рейтинга.</p>
    <?php endif; ?>
</section>
    </main>
</body>
</html>