<?php
require 'config.php';
checkAdminAccess();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query('
    SELECT l.*, u.username, u.full_name 
    FROM activity_logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
');
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - APM Склад</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>APM Склад - История действий</h1>
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
        <section class="reports">
            <h2>История действий</h2>
            
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['created_at'] ?></td>
                        <td><?= htmlspecialchars($log['full_name']) ?> (<?= htmlspecialchars($log['username']) ?>)</td>
                        <td><?= $log['action'] ?></td>
                        <td><?= htmlspecialchars($log['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>