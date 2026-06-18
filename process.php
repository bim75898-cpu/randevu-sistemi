<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/lib/MailService.php';
require_once __DIR__ . '/lib/SmsService.php';
Security::init($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Security::logSecurityEvent('method', 'Invalid request method on process.php');
    header('Location: index.php');
    exit;
}

if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    Security::logSecurityEvent('csrf', 'CSRF token validation failed on form submission');
    header('Location: index.php?error=' . urlencode('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.'));
    exit;
}

Security::checkHoneypot();

$service_id = $_POST['service_id'] ?? null;
$customer_name = Security::sanitizeInput(trim($_POST['customer_name'] ?? ''));
$customer_email = Security::sanitizeInput(trim($_POST['customer_email'] ?? ''));
$customer_phone = Security::sanitizeInput(trim($_POST['customer_phone'] ?? ''));
$appointment_date = $_POST['appointment_date'] ?? null;
$appointment_time = $_POST['appointment_time'] ?? null;
$notes = Security::sanitizeInput(trim($_POST['notes'] ?? ''));
$employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

$errors = [];

if (!$service_id || !ctype_digit($service_id)) $errors[] = 'Geçerli bir hizmet seçin.';
if (empty($customer_name) || strlen($customer_name) > 100) $errors[] = 'Geçerli bir ad girin (max 100 karakter).';
if (!Security::validateEmail($customer_email)) $errors[] = 'Geçerli bir e-posta adresi girin.';
if (!Security::validatePhone($customer_phone)) $errors[] = 'Geçerli bir telefon numarası girin.';
if (!Security::validateDate($appointment_date) || strtotime($appointment_date) < strtotime('today')) {
    $errors[] = 'Geçerli bir tarih seçin.';
}
if (!Security::validateTime($appointment_time)) $errors[] = 'Geçerli bir saat seçin.';
if (strlen($notes) > 1000) $errors[] = 'Not çok uzun (max 1000 karakter).';

if (!empty($errors)) {
    Security::logSecurityEvent('validation', 'Form validation failed: ' . implode(', ', $errors));
    header('Location: index.php?error=' . urlencode(implode(' ', $errors)));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT duration, price, name, requires_payment FROM services WHERE id = ? AND is_active = 1");
    $stmt->execute([(int)$service_id]);
    $service = $stmt->fetch();

    if (!$service) {
        Security::logSecurityEvent('invalid_service', "Invalid service ID: $service_id");
        header('Location: index.php?error=Seçilen hizmet bulunamadı.');
        exit;
    }

    $duration = (int) $service['duration'];
    $selectedTime = strtotime($appointment_time);
    $selectedEnd = $selectedTime + $duration * 60;

    // Check for time conflict (for employee or all)
    if ($employee_id) {
        $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled' AND employee_id = ?");
        $stmt->execute([$appointment_date, $employee_id]);
    } else {
        $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
        $stmt->execute([$appointment_date]);
    }
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($booked as $bt) {
        $btSec = strtotime($bt);
        $btEnd = $btSec + $duration * 60;
        if (max($selectedTime, $btSec) < min($selectedEnd, $btEnd)) {
            header('Location: index.php?error=Seçilen saat dolu. Lütfen başka bir saat seçin.');
            exit;
        }
    }

    // Auto-create or update customer
    $customerId = null;
    if ($customer_email) {
        $stmt = $pdo->prepare("SELECT id, total_appointments, total_spent, loyalty_points FROM customers WHERE email = ?");
        $stmt->execute([$customer_email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $customerId = $existing['id'];
            $pdo->prepare("UPDATE customers SET name = ?, phone = ?, last_visit = ?, total_appointments = total_appointments + 1, total_spent = total_spent + ? WHERE id = ?")
                ->execute([$customer_name, $customer_phone, $appointment_date, $service['price'], $customerId]);
            // Loyalty: 1 point per 50 TL
            $pointsEarned = floor($service['price'] / 50);
            $pdo->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?")->execute([$pointsEarned, $customerId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, total_appointments, total_spent, last_visit, loyalty_points) VALUES (?, ?, ?, 1, ?, ?, ?)");
            $pointsEarned = floor($service['price'] / 50);
            $stmt->execute([$customer_name, $customer_email, $customer_phone, $service['price'], $appointment_date, $pointsEarned]);
            $customerId = $pdo->lastInsertId();
        }
    }

    $cancelToken = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("INSERT INTO appointments (service_id, employee_id, customer_id, customer_name, customer_email, customer_phone, appointment_date, appointment_time, notes, cancel_token, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([(int)$service_id, $employee_id, $customerId, $customer_name, $customer_email, $customer_phone, $appointment_date, $appointment_time, $notes, $cancelToken, $cancelToken]);

    $appointmentId = $pdo->lastInsertId();
    $appointment = [
        'id' => $appointmentId,
        'service_name' => $service['name'],
        'price' => $service['price'],
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'cancel_token' => $cancelToken,
    ];

    // Auto-deduct from customer package if available
    if ($customerId) {
        $cp = $pdo->prepare("SELECT cp.id, cp.sessions_used, cp.sessions_total FROM customer_packages cp JOIN package_items pi ON pi.package_id = cp.package_id WHERE cp.customer_id = ? AND pi.service_id = ? AND cp.status = 'active' AND (cp.expires_at IS NULL OR cp.expires_at >= CURDATE()) AND cp.sessions_used < cp.sessions_total ORDER BY cp.expires_at ASC LIMIT 1");
        $cp->execute([$customerId, (int)$service_id]);
        $cpRow = $cp->fetch();
        if ($cpRow) {
            $newUsed = $cpRow['sessions_used'] + 1;
            $upd = $pdo->prepare("UPDATE customer_packages SET sessions_used = ? WHERE id = ?");
            $upd->execute([$newUsed, $cpRow['id']]);
            $ins = $pdo->prepare("INSERT INTO package_usages (customer_package_id, appointment_id) VALUES (?, ?)");
            $ins->execute([$cpRow['id'], $appointmentId]);
            $appointment['package_used'] = true;
        }
    }

    MailService::sendAppointmentConfirmation($appointment, $cancelToken);
    SmsService::sendAppointmentConfirmation($appointment);

    if (!empty($service['requires_payment'])) {
        require_once __DIR__ . '/lib/PaymentService.php';
        header('Location: customer/pay.php?token=' . urlencode($cancelToken));
    } else {
        header('Location: index.php?success=Randevunuz başarıyla oluşturuldu! E-posta ve SMS ile bilgilendirileceksiniz.&token=' . urlencode($cancelToken));
    }
} catch (Exception $e) {
    Security::logSecurityEvent('error', 'Process error: ' . $e->getMessage());
    header('Location: index.php?error=Bir hata oluştu. Lütfen tekrar deneyin.');
}
