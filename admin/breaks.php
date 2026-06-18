<?php
require __DIR__ . '/_init.php';
$tab = 'breaks';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_breaks'])) {
    $pdo->exec("DELETE FROM break_times");
    $breaks = $_POST['breaks'] ?? [];
    foreach ($breaks as $b) {
        if (!empty($b['start']) && !empty($b['end'])) {
            $empId = !empty($b['employee_id']) ? (int)$b['employee_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO break_times (employee_id, day_of_week, start_time, end_time, label) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$empId, (int)$b['day'], $b['start'], $b['end'], Security::sanitizeInput($b['label'] ?? '')]);
        }
    }
    header('Location: breaks.php');
    exit;
}

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/breaks.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
