<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
Security::init($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['package_id'])) {
    header('Location: packages.php');
    exit;
}

if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header('Location: packages.php?error=' . urlencode('Güvenlik doğrulaması başarısız.'));
    exit;
}

$pkgId = (int)$_POST['package_id'];
$name = Security::sanitizeInput(trim($_POST['customer_name'] ?? ''));
$email = Security::sanitizeInput(trim($_POST['customer_email'] ?? ''));
$phone = Security::sanitizeInput(trim($_POST['customer_phone'] ?? ''));

if (!$name || !$email || !$phone) {
    header('Location: packages.php?error=' . urlencode('Lütfen tüm alanları doldurun.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
$stmt->execute([$pkgId]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header('Location: packages.php?error=' . urlencode('Paket bulunamadı.'));
    exit;
}

// Find or create customer
$custStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
$custStmt->execute([$email]);
$customer = $custStmt->fetch();

if ($customer) {
    $customerId = $customer['id'];
    $upd = $pdo->prepare("UPDATE customers SET name = ?, phone = ? WHERE id = ?");
    $upd->execute([$name, $phone, $customerId]);
} else {
    $ins = $pdo->prepare("INSERT INTO customers (name, email, phone, total_appointments, total_spent, last_visit) VALUES (?, ?, ?, 0, 0, NULL)");
    $ins->execute([$name, $email, $phone]);
    $customerId = $pdo->lastInsertId();
}

// Create customer_package
$expiresAt = date('Y-m-d', strtotime("+{$pkg['valid_days']} days"));
$ins = $pdo->prepare("INSERT INTO customer_packages (customer_id, package_id, sessions_used, sessions_total, purchased_price, expires_at, status) VALUES (?, ?, 0, ?, ?, ?, 'active')");
$ins->execute([$customerId, $pkgId, $pkg['total_sessions'], $pkg['price'], $expiresAt]);

header('Location: packages.php?success=' . urlencode("Paket başarıyla satın alındı! Randevu almak için ana sayfaya gidin."));
exit;
