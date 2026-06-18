<?php
require __DIR__ . '/_init.php';
$tab = 'series';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_series'])) {
    $svcId = (int)($_POST['service_id'] ?? 0);
    $empId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $cName = Security::sanitizeInput(trim($_POST['customer_name'] ?? ''));
    $cEmail = Security::sanitizeInput(trim($_POST['customer_email'] ?? ''));
    $cPhone = Security::sanitizeInput(trim($_POST['customer_phone'] ?? ''));
    $time = $_POST['appointment_time'] ?? '';
    $freq = $_POST['frequency'] ?? 'weekly';
    $dayOfWeek = (int)($_POST['day_of_week'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?: null;
    $notes = Security::sanitizeInput(trim($_POST['notes'] ?? ''));
    if ($svcId && $cName && $cEmail && $time && $startDate) {
        $stmt = $pdo->prepare("INSERT INTO appointment_series (service_id, employee_id, customer_name, customer_email, customer_phone, appointment_time, frequency, day_of_week, start_date, end_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$svcId, $empId, $cName, $cEmail, $cPhone, $time, $freq, $dayOfWeek, $startDate, $endDate, $notes]);
        $seriesId = $pdo->lastInsertId();
        $current = strtotime($startDate);
        $end = $endDate ? strtotime($endDate) : strtotime('+6 months', $current);
        $generated = 0;
        $cancelToken = bin2hex(random_bytes(32));
        while ($current <= $end && $generated < 52) {
            $d = date('Y-m-d', $current);
            $dw = (int)date('w', $current);
            if ($dw === $dayOfWeek) {
                $stmt = $pdo->prepare("INSERT INTO appointments (service_id, employee_id, customer_name, customer_email, customer_phone, appointment_date, appointment_time, notes, cancel_token, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$svcId, $empId, $cName, $cEmail, $cPhone, $d, $time, $notes, $cancelToken, $cancelToken]);
                $generated++;
                $cancelToken = bin2hex(random_bytes(32));
            }
            $current = $freq === 'monthly' ? strtotime('+1 month', $current) : ($freq === 'biweekly' ? strtotime('+2 weeks', $current) : strtotime('+1 week', $current));
        }
    }
    header('Location: series.php');
    exit;
}

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/series.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
