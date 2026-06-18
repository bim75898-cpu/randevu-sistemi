<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/Language.php';
Language::init($pdo);

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$appointment = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, e.name as employee_name FROM appointments a JOIN services s ON a.service_id = s.id LEFT JOIN employees e ON a.employee_id = e.id WHERE a.token = ?");
    $stmt->execute([$token]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $error = 'Geçersiz bağlantı.';
    } elseif ($appointment['checked_in_at']) {
        $success = true;
        $alreadyCheckedIn = true;
    } else {
        $alreadyCheckedIn = false;
        if (isset($_POST['checkin'])) {
            $upd = $pdo->prepare("UPDATE appointments SET checked_in_at = NOW() WHERE id = ?");
            $upd->execute([$appointment['id']]);
            $appointment['checked_in_at'] = date('Y-m-d H:i:s');
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <?php if (!$token || $error): ?>
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Geçersiz Bağlantı</h1>
                <p class="text-sm text-gray-500 mt-2"><?= htmlspecialchars($error ?: 'Bu link geçersiz.') ?></p>
            <?php elseif ($success && $alreadyCheckedIn): ?>
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Zaten Check-In Yapılmış</h1>
                <p class="text-sm text-gray-500 mt-2">Bu randevu için daha önce giriş yapılmış. Hoş geldiniz!</p>
                <p class="text-xs text-gray-400 mt-3"><?= date('d.m.Y H:i', strtotime($appointment['checked_in_at'])) ?></p>
            <?php elseif ($success): ?>
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Hoş Geldiniz!</h1>
                <p class="text-sm text-gray-600 mt-2">Başarıyla giriş yaptınız.</p>
                <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($appointment['customer_name']) ?> · <?= htmlspecialchars($appointment['service_name']) ?> · <?= $appointment['appointment_date'] ?> <?= $appointment['appointment_time'] ?></p>
            <?php else: ?>
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Giriş Yap</h1>
                <p class="text-sm text-gray-600 mt-1">Randevunuza hoş geldiniz!</p>
                <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars($appointment['customer_name']) ?> · <?= htmlspecialchars($appointment['service_name']) ?> · <?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?> <?= $appointment['appointment_time'] ?></p>
                <form method="POST" class="mt-6">
                    <button type="submit" name="checkin" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md">Check-In Yap</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
