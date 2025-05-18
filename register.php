<?php
require 'config.php';
checkAdminAccess();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role']; 

    if (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $hashed_password, $full_name, $role]);
            
            logActivity($pdo, 'register_user', 'Зарегистрирован новый пользователь: ' . $username . ' (роль: ' . $role . ')');

            $success = 'Новый пользователь успешно зарегистрирован!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация нового работника</title>
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
        <section class="register-form">
            <h2>Регистрация нового пользователя</h2>
            
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Логин:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Полное имя:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Роль:</label>
                    <select id="role" name="role" required>
                        <option value="admin">Работник склада</option>
                        <option value="customer">Заказчик</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Зарегистрировать</button>
                <a href="dashboard.php" class="btn">Назад</a>
            </form>
        </section>
    </main>
</body>
</html>