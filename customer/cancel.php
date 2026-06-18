<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
Security::init($pdo);

$token = $_GET['token'] ?? '';
$reschedule = $_GET['reschedule'] ?? false;
$step = 'confirm';

if ($token) {
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.duration, s.price, s.requires_payment FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.cancel_token = ?");
    $stmt->execute([$token]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $step = 'invalid';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['cancel'])) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$appointment['id']]);
            $step = 'cancelled';
        } elseif (isset($_POST['reschedule'])) {
            $newDate = $_POST['new_date'] ?? '';
            $newTime = $_POST['new_time'] ?? '';
            if ($newDate && $newTime) {
                $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'pending' WHERE id = ? AND cancel_token = ?");
                $stmt->execute([$newDate, $newTime, $appointment['id'], $token]);
                $appointment['appointment_date'] = $newDate;
                $appointment['appointment_time'] = $newTime;
                $appointment['status'] = 'pending';
                $step = 'rescheduled';
            }
        }
    } elseif ($reschedule) {
        $step = 'reschedule_form';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu İşlemleri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg px-4">

<?php if ($step === 'invalid'): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Geçersiz Bağlantı</h1>
            <p class="text-gray-500 mt-2">Bu bağlantı geçersiz veya süresi dolmuş.</p>
            <a href="../index.php" class="inline-block mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">Ana Sayfa</a>
        </div>

<?php elseif ($step === 'confirm'): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Randevu İşlemleri</h1>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="text-sm text-gray-600"><?= htmlspecialchars($appointment['service_name']) ?></div>
                <div class="text-lg font-bold text-gray-900 mt-1"><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?> — <?= date('H:i', strtotime($appointment['appointment_time'])) ?></div>
                <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($appointment['customer_name']) ?></div>
                <?php if ($appointment['requires_payment']): ?>
                    <div class="mt-2 pt-2 border-t border-gray-200 flex justify-between text-sm">
                        <span class="text-gray-500">Ödeme</span>
                        <span class="<?= $appointment['payment_status'] === 'paid' ? 'text-green-600 font-medium' : 'text-amber-600 font-medium' ?>">
                            <?= $appointment['payment_status'] === 'paid' ? 'Ödendi' : ($appointment['payment_status'] === 'refunded' ? 'İade Edildi' : 'Bekliyor') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST" class="space-y-3">
                <button type="submit" name="cancel" class="w-full py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all shadow-md">Randevuyu İptal Et</button>
                <a href="?token=<?= urlencode($token) ?>&reschedule=1" class="block w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-all text-center">Tarih Değiştir</a>
                <a href="../index.php" class="block text-center text-sm text-gray-400 hover:text-indigo-600">Ana Sayfa</a>
            </form>
        </div>

<?php elseif ($step === 'reschedule_form'): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <h1 class="text-xl font-bold text-gray-900 mb-2">Tarih Değiştir</h1>
            <p class="text-gray-500 text-sm mb-6">Yeni tarih ve saat seçin.</p>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Yeni Tarih</label>
                        <input type="date" name="new_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Yeni Saat</label>
                        <input type="time" name="new_time" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <button type="submit" name="reschedule" class="w-full mt-6 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all shadow-md">Değiştir</button>
                <a href="?token=<?= urlencode($token) ?>" class="block mt-3 text-center text-sm text-gray-400 hover:text-indigo-600">Geri</a>
            </form>
        </div>

<?php elseif ($step === 'cancelled'): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Randevu İptal Edildi</h1>
            <p class="text-gray-500 mt-2">Randevunuz başarıyla iptal edildi.</p>
            <a href="../index.php" class="inline-block mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">Ana Sayfa</a>
        </div>

<?php elseif ($step === 'rescheduled'): ?>
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Randevu Güncellendi</h1>
            <p class="text-gray-500 mt-2">Yeni randevunuz: <?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?> — <?= date('H:i', strtotime($appointment['appointment_time'])) ?></p>
            <a href="../index.php" class="inline-block mt-6 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">Ana Sayfa</a>
        </div>
<?php endif; ?>

    </div>
</body>
</html>
