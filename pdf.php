<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/lib/PdfService.php';
Security::init($pdo);

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date = $_GET['date'] ?? date('Y-m-d');

if ($type === 'appointment' && $id) {
    Security::verifyAdminSession();
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.price FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.id = ?");
    $stmt->execute([$id]);
    $appt = $stmt->fetch();
    if ($appt) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="randevu_' . $id . '.pdf"');
        echo PdfService::appointmentReceipt($appt);
    }
} elseif ($type === 'daily' && $date) {
    Security::verifyAdminSession();
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.price, e.name as employee_name FROM appointments a JOIN services s ON a.service_id = s.id LEFT JOIN employees e ON a.employee_id = e.id WHERE a.appointment_date = ? ORDER BY a.appointment_time");
    $stmt->execute([$date]);
    $appts = $stmt->fetchAll();
    $total = array_sum(array_column($appts, 'price'));
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="rapor_' . $date . '.pdf"');
    echo PdfService::dailyReport($date, $appts, $total);
}
