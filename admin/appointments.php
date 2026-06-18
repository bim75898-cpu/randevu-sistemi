<?php
require __DIR__ . '/_init.php';
$tab = 'appointments';

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $allowed = ['confirmed', 'cancelled', 'completed'];
    if (in_array($_GET['action'], $allowed)) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['action'], $id]);

        $stmt2 = $pdo->prepare("SELECT a.*, s.name as service_name, s.price FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.id = ?");
        $stmt2->execute([$id]);
        $appt = $stmt2->fetch();
        if ($appt) {
            MailService::sendStatusUpdate($appt);
            SmsService::sendStatusUpdate($appt);
            if ($_GET['action'] === 'completed') {
                MailService::sendAppointmentCompleted($appt);
            }
        }

        header('Location: appointments.php');
        exit;
    }
}

$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sql = "SELECT a.*, s.name as service_name, s.price, e.name as employee_name FROM appointments a JOIN services s ON a.service_id = s.id LEFT JOIN employees e ON a.employee_id = e.id WHERE 1=1";
$params = [];
if ($status_filter) { $sql .= " AND a.status = ?"; $params[] = $status_filter; }
if ($date_from) { $sql .= " AND a.appointment_date >= ?"; $params[] = $date_from; }
if ($date_to) { $sql .= " AND a.appointment_date <= ?"; $params[] = $date_to; }
if ($currentBranchId) { $sql .= " AND (a.branch_id = ? OR a.branch_id IS NULL)"; $params[] = $currentBranchId; }
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/appointments.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
