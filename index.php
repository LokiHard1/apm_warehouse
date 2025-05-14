<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: customer_dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>