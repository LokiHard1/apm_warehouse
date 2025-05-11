<?php
require 'config.php';

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
        <a href="reports.php" <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'class="active"' : '' ?>>Отчеты</a>
        <?php if ($_SESSION['username'] === 'admin'): ?>
            <a href="register.php" <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'class="active"' : '' ?>>Добавить работника</a>
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
    </main>
</body>
</html>