<?php
session_start();

$host = 'localhost';
$db   = 'apm_warehouse';
$user = 'root';
$pass = 'root'; 
$port = 8889;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function logActivity($pdo, $action, $description) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $action, $description]);
    }
}

function checkAdminAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: customer_dashboard.php');
        exit;
    }
}

function checkCustomerAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        header('Location: dashboard.php');
        exit;
    }
}
?>