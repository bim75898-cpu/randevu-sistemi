<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../lib/PaymentService.php';
Security::init($pdo);

$token = $_GET['token'] ?? '';
$error = '';
$paymentHtml = '';

if ($token) {
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.duration, s.price, s.requires_payment FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.cancel_token = ?");
    $stmt->execute([$token]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $error = 'Geçersiz bağlantı.';
    } elseif (!$appointment['requires_payment']) {
        $error = 'Bu randevu için ödeme gerekmiyor.';
    } elseif ($appointment['payment_status'] === 'paid') {
        $error = 'Bu randevu zaten ödenmiş.';
    } elseif ($appointment['status'] === 'cancelled') {
        $error = 'İptal edilmiş randevu için ödeme yapılamaz.';
    } else {
        $paymentHtml = PaymentService::createPaymentPage($appointment);
    }
} else {
    $error = 'Geçersiz bağlantı.';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme - Randevu Sistemi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg px-4">
        <?php if ($error): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Ödeme Yapılamaz</h1>
            <p class="text-gray-500 mt-2"><?= htmlspecialchars($error) ?></p>
            <a href="../index.php" class="inline-block mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">Ana Sayfa</a>
        </div>
        <?php else: ?>
            <?= $paymentHtml ?>
        <?php endif; ?>
    </div>
</body>
</html>
