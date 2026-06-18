<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
Security::init($pdo);

header('Content-Type: application/json');

$service_id = $_GET['service_id'] ?? null;
$date = $_GET['date'] ?? null;
$employee_id = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int)$_GET['employee_id'] : null;

if (!$service_id || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'Eksik parametre']);
    exit;
}

if (!ctype_digit($service_id) || !Security::validateDate($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz parametre']);
    exit;
}

$stmt = $pdo->prepare("SELECT duration, requires_payment FROM services WHERE id = ? AND is_active = 1");
$stmt->execute([(int)$service_id]);
$service = $stmt->fetch();

if (!$service) {
    echo json_encode(['error' => 'Hizmet bulunamadı']);
    exit;
}

$duration = (int) $service['duration'];
$dayOfWeek = (int) date('w', strtotime($date));

// Use employee_hours if employee selected, otherwise business working_hours
if ($employee_id) {
    $stmt = $pdo->prepare("SELECT * FROM employee_hours WHERE employee_id = ? AND day_of_week = ?");
    $stmt->execute([$employee_id, $dayOfWeek]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM working_hours WHERE day_of_week = ?");
    $stmt->execute([$dayOfWeek]);
}
$hours = $stmt->fetch();

if (!$hours || !$hours['is_open']) {
    echo json_encode(['error' => 'Bu gün için uygun saat bulunamadı']);
    exit;
}

$openTime = strtotime($hours['open_time']);
$closeTime = strtotime($hours['close_time']);

// Fetch break times for this day (employee-specific or general)
$breakSql = "SELECT start_time, end_time FROM break_times WHERE day_of_week = ? AND (employee_id IS NULL";
$breakParams = [$dayOfWeek];
if ($employee_id) {
    $breakSql .= " OR employee_id = ?";
    $breakParams[] = $employee_id;
}
$breakSql .= ") ORDER BY start_time";
$stmt = $pdo->prepare($breakSql);
$stmt->execute($breakParams);
$breaks = $stmt->fetchAll();

// Fetch booked appointments (for employee or all)
if ($employee_id) {
    $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled' AND employee_id = ?");
    $stmt->execute([$date, $employee_id]);
} else {
    $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
    $stmt->execute([$date]);
}
$bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

function timeInBreak($time, $breaks) {
    foreach ($breaks as $b) {
        $bStart = strtotime($b['start_time']);
        $bEnd = strtotime($b['end_time']);
        if ($time >= $bStart && $time < $bEnd) return true;
    }
    return false;
}

$slots = [];
$current = $openTime;

while ($current + $duration * 60 <= $closeTime) {
    $timeStr = date('H:i', $current);

    // Check if in break time
    $inBreak = timeInBreak($current, $breaks);
    if ($inBreak) {
        $slots[] = ['time' => $timeStr, 'available' => false];
        $current += 30 * 60;
        continue;
    }

    // Check if any part of the appointment falls in break
    $slotEnd = $current + $duration * 60;
    $overlapsBreak = false;
    foreach ($breaks as $b) {
        $bStart = strtotime($b['start_time']);
        $bEnd = strtotime($b['end_time']);
        if (max($current, $bStart) < min($slotEnd, $bEnd)) {
            $overlapsBreak = true;
            break;
        }
    }
    if ($overlapsBreak) {
        $slots[] = ['time' => $timeStr, 'available' => false];
        $current += 30 * 60;
        continue;
    }

    $isBooked = false;
    foreach ($bookedTimes as $bt) {
        $btSec = strtotime($bt);
        $btEnd = $btSec + $duration * 60;
        if (max($current, $btSec) < min($slotEnd, $btEnd)) {
            $isBooked = true;
            break;
        }
    }

    $isPast = ($date === date('Y-m-d') && $current <= time());

    $slots[] = [
        'time' => $timeStr,
        'available' => !$isBooked && !$isPast
    ];

    $current += 30 * 60;
}

echo json_encode(['slots' => $slots]);
