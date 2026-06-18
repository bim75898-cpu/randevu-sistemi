<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/PaymentService.php';

$paymentResult = PaymentService::processCallback($_POST);

if ($paymentResult['status'] === 'success' && isset($_POST['conversationId'])) {
    $stmt = $pdo->prepare("UPDATE appointments SET payment_status = 'paid', payment_id = ? WHERE id = ?");
    $stmt->execute([$paymentResult['paymentId'], (int)$_POST['conversationId']]);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sonucu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <?php if ($paymentResult['status'] === 'success'): ?>
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Ödeme Başarılı</h1>
            <p class="text-gray-500 mt-2">Ödemeniz alınmıştır. Randevunuz onaylanmayı bekliyor.</p>
            <?php else: ?>
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Ödeme Başarısız</h1>
            <p class="text-gray-500 mt-2">Ödeme işlemi sırasında bir hata oluştu.</p>
            <?php endif; ?>
            <a href="../index.php" class="inline-block mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">Ana Sayfa</a>
        </div>
    </div>
</body>
</html>
