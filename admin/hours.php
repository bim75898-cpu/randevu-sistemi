<?php
require __DIR__ . '/_init.php';
$tab = 'hours';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = $_POST['hours'] ?? [];
    foreach ($days as $dayId => $data) {
        $isOpen = !empty($data['is_open']) ? 1 : 0;
        $openTime = $data['open'] ?? '09:00';
        $closeTime = $data['close'] ?? '18:00';
        $stmt = $pdo->prepare("UPDATE working_hours SET is_open = ?, open_time = ?, close_time = ? WHERE id = ?");
        $stmt->execute([$isOpen, $openTime, $closeTime, (int)$dayId]);
    }
    header('Location: hours.php');
    exit;
}

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/hours.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
