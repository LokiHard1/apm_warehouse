<?php
require 'config.php';
checkAdminAccess();

$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$allowed_sorts = ['created_at', 'username', 'action'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'created_at';
}

$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$stmt = $pdo->prepare('
    SELECT l.*, u.username, u.full_name 
    FROM activity_logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY ' . $sort . ' ' . $order
);
$stmt->execute();
$logs = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $format = $_POST['format'];
    
    if ($format === 'pdf') {
        exportToPDF($logs, $sort, $order);
    } elseif ($format === 'txt') {
        exportToTXT($logs, $sort, $order);
    }
}

function exportToPDF($logs, $sort, $order) {

    require_once('tcpdf/tcpdf.php');
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('APM Склад');
    $pdf->SetAuthor('APM Склад');
    $pdf->SetTitle('История действий системы');
    $pdf->SetSubject('Отчет о действиях пользователей');
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    $pdf->AddPage();
    
    $pdf->SetFont('dejavusans', '', 10);
    
    $html = '<h1 style="text-align:center;">История действий системы</h1>';
    $html .= '<p style="text-align:center;"><b>Сформировано:</b> ' . date('d.m.Y H:i') . '</p>';
    $html .= '<p style="text-align:center;"><b>Сортировка:</b> ' . htmlspecialchars($sort) . ' (' . htmlspecialchars($order) . ')</p>';
    
    $html .= '<table border="1" cellpadding="4" cellspacing="0">';
    $html .= '<thead>
        <tr style="background-color:#f2f2f2;">
            <th width="20%"><b>Дата и время</b></th>
            <th width="20%"><b>Пользователь</b></th>
            <th width="15%"><b>Действие</b></th>
            <th width="45%"><b>Описание</b></th>
        </tr>
    </thead>
    <tbody>';
    
    foreach ($logs as $log) {
        $html .= '<tr>
            <td width="20%">' . htmlspecialchars($log['created_at']) . '</td>
            <td width="20%">' . htmlspecialchars($log['full_name']) . '</td>
            <td width="15%">' . htmlspecialchars($log['action']) . '</td>
            <td width="45%">' . htmlspecialchars($log['description']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->SetY(-15);
    $pdf->SetFont('dejavusans', 'I', 8);
    $pdf->Cell(0, 10, 'Страница ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    
    $pdf->Output('history_actions_' . date('Y-m-d_H-i') . '.pdf', 'D');
    exit;
}

function exportToTXT($logs, $sort, $order) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="history_' . date('Y-m-d') . '.txt"');
    
    echo "История действий\n";
    echo "Сортировка: $sort ($order)\n\n";
    echo str_pad('Дата', 20) . str_pad('Пользователь', 30) . str_pad('Действие', 20) . "Описание\n";
    echo str_repeat('-', 100) . "\n";
    
    foreach ($logs as $log) {
        echo str_pad($log['created_at'], 20) . 
             str_pad($log['full_name'], 30) . 
             str_pad($log['action'], 20) . 
             $log['description'] . "\n";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт истории - APM Склад</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>APM Склад - Экспорт истории</h1>
        <div class="user-info">
            Вы вошли как: <?= htmlspecialchars($_SESSION['full_name']) ?>
            <a href="logout.php" class="logout">Выйти</a>
        </div>
    </header>
    
    <nav class="main-nav">
        <a href="dashboard.php">Панель управления</a>
        <a href="reports.php">История действий</a>
        <a href="export_logs.php" class="active">Экспорт истории</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="register.php">Добавить пользователя</a>
        <?php endif; ?>
    </nav>
    
    <main>
        <section class="export-section">
            <h2>Экспорт истории действий</h2>
            
            <div class="sort-options">
                <h3>Сортировка</h3>
                <a href="?sort=created_at&order=DESC" class="btn <?= $sort === 'created_at' && $order === 'DESC' ? 'active' : '' ?>">Новые сначала</a>
                <a href="?sort=created_at&order=ASC" class="btn <?= $sort === 'created_at' && $order === 'ASC' ? 'active' : '' ?>">Старые сначала</a>
                <a href="?sort=username&order=ASC" class="btn <?= $sort === 'username' ? 'active' : '' ?>">По пользователю (А-Я)</a>
                <a href="?sort=username&order=DESC" class="btn <?= $sort === 'username' && $order === 'DESC' ? 'active' : '' ?>">По пользователю (Я-А)</a>
                <a href="?sort=action&order=ASC" class="btn <?= $sort === 'action' ? 'active' : '' ?>">По типу действия</a>
            </div>
            
            <form method="POST" class="export-form">
                <div class="form-group">
                    <label for="format">Формат экспорта:</label>
                    <select id="format" name="format" required>
                        <option value="pdf">PDF</option>
                        <option value="txt">Текстовый файл (TXT)</option>
                    </select>
                </div>
                
                <button type="submit" name="export" class="btn">Экспортировать</button>
            </form>
            
            <div class="preview">
                <h3>Предпросмотр (первые 10 записей)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Пользователь</th>
                            <th>Действие</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($logs, 0, 10) as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>