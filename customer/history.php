<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
Security::init($pdo);

$appointments = [];
$searched = false;
$email = '';
$customerPoints = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Security::sanitizeInput(trim($_POST['email'] ?? ''));
    $phone = Security::sanitizeInput(trim($_POST['phone'] ?? ''));

    if ($email || $phone) {
        $sql = "SELECT a.*, s.name as service_name, s.price, s.duration, s.requires_payment FROM appointments a JOIN services s ON a.service_id = s.id WHERE";
        $params = [];
        $conditions = [];

        if ($email) {
            $conditions[] = " a.customer_email = ?";
            $params[] = $email;
        }
        if ($phone) {
            $conditions[] = " a.customer_phone = ?";
            $params[] = $phone;
        }
        $sql .= implode(' OR ', $conditions);
        $sql .= " ORDER BY a.appointment_date DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();
        $searched = true;

        // Get loyalty points
        if ($email) {
            $pStmt = $pdo->prepare("SELECT loyalty_points FROM customers WHERE email = ?");
            $pStmt->execute([$email]);
            $cp = $pStmt->fetch();
            $customerPoints = $cp ? (int)$cp['loyalty_points'] : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Geçmişim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <a href="../index.php" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xl font-bold text-gray-800">Randevu<span class="text-indigo-600">Geçmişim</span></span>
            </a>
            <a href="../index.php" class="text-sm text-gray-400 hover:text-indigo-600 transition-colors">← Geri</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Randevu Geçmişim</h1>
            <p class="text-gray-500">E-posta veya telefon numaranız ile randevularınızı görüntüleyin.</p>
        </div>

        <form method="POST" class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sm:p-8 mb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-posta Adresiniz</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="ornek@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefon Numaranız</label>
                    <input type="tel" name="phone" placeholder="05XX XXX XX XX" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <button type="submit" class="mt-4 w-full sm:w-auto px-8 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all shadow-md">Sorgula</button>
        </form>

        <?php if ($searched): ?>
            <?php if (empty($appointments)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-gray-500">Bu bilgilere ait randevu bulunamadı.</p>
                </div>
            <?php else: ?>
                <?php if ($customerPoints > 0): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center gap-3 mb-4">
                    <span class="text-2xl">🏆</span>
                    <div>
                        <p class="font-semibold text-amber-800"><?= $customerPoints ?> Sadakat Puanınız var!</p>
                        <p class="text-xs text-amber-600">Her 50 TL harcamada 1 puan kazanırsınız.</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                if ($email) {
                    $cpStmt = $pdo->prepare("SELECT cp.*, p.name as package_name, p.total_sessions FROM customer_packages cp JOIN packages p ON cp.package_id = p.id WHERE cp.customer_id = (SELECT id FROM customers WHERE email = ?) AND cp.status = 'active' ORDER BY cp.expires_at ASC");
                    $cpStmt->execute([$email]);
                    $activePacks = $cpStmt->fetchAll();
                    if (!empty($activePacks)):
                ?>
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-5 py-4 mb-6">
                    <p class="text-sm font-semibold text-indigo-800 mb-3">📦 Aktif Paketleriniz</p>
                    <div class="space-y-2">
                        <?php foreach ($activePacks as $ap):
                            $remaining = $ap['sessions_total'] - $ap['sessions_used'];
                            $pct = $ap['sessions_total'] > 0 ? round($ap['sessions_used'] / $ap['sessions_total'] * 100) : 0;
                        ?>
                        <div class="flex items-center justify-between text-sm bg-white rounded-lg px-3 py-2 border border-indigo-100">
                            <span class="font-medium text-indigo-900"><?= htmlspecialchars($ap['package_name']) ?></span>
                            <div class="flex items-center gap-3">
                                <div class="w-20 h-2 bg-indigo-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $remaining === 0 ? 'bg-red-400' : 'bg-indigo-500' ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="text-xs text-indigo-600 font-medium"><?= $remaining ?>/<?= $ap['sessions_total'] ?> kaldı</span>
                                <?php if ($ap['expires_at']): ?><span class="text-xs text-gray-400"><?= date('d.m.Y', strtotime($ap['expires_at'])) ?>'e kadar</span><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; } ?>
                <div class="space-y-3">
                    <?php foreach ($appointments as $a):
                        $badge = match($a['status']) { 'pending' => 'bg-yellow-100 text-yellow-800', 'confirmed' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800', 'completed' => 'bg-blue-100 text-blue-800', default => 'bg-gray-100 text-gray-800' };
                        $label = match($a['status']) { 'pending' => 'Bekliyor', 'confirmed' => 'Onaylı', 'cancelled' => 'İptal', 'completed' => 'Tamamlandı', default => $a['status'] };
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($a['service_name']) ?></h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= date('d.m.Y', strtotime($a['appointment_date'])) ?> —
                                    <?= date('H:i', strtotime($a['appointment_time'])) ?>
                                </p>
                                <?php if ($a['notes']): ?>
                                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($a['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $badge ?>"><?= $label ?></span>
                                <p class="text-sm font-bold text-indigo-600 mt-1.5"><?= number_format($a['price'], 0, ',', '.') ?> TL</p>
                                <?php if ($a['requires_payment']): ?>
                                    <?php
                                    $payBadge = match($a['payment_status']) { 'paid' => 'bg-green-100 text-green-700', 'refunded' => 'bg-purple-100 text-purple-700', default => 'bg-amber-100 text-amber-700' };
                                    $payLabel = match($a['payment_status']) { 'paid' => 'Ödendi', 'refunded' => 'İade Edildi', default => 'Ödeme Bekliyor' };
                                    ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium mt-1 <?= $payBadge ?>"><?= $payLabel ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($a['cancel_token'] && $a['status'] !== 'cancelled' && $a['status'] !== 'completed'): ?>
                            <div class="mt-3 pt-3 border-t border-gray-100 flex gap-2 flex-wrap">
                                <a href="cancel.php?token=<?= urlencode($a['cancel_token']) ?>" class="text-xs text-red-500 hover:text-red-700 font-medium">İptal Et</a>
                                <a href="cancel.php?token=<?= urlencode($a['cancel_token']) ?>&reschedule=1" class="text-xs text-indigo-500 hover:text-indigo-700 font-medium">Tarih Değiştir</a>
                                <?php if ($a['requires_payment'] && $a['payment_status'] !== 'paid'): ?>
                                    <a href="pay.php?token=<?= urlencode($a['cancel_token']) ?>" class="text-xs text-amber-600 hover:text-amber-800 font-medium">Ödeme Yap</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="../index.php" class="text-sm text-gray-400 hover:text-indigo-600 transition-colors">← Yeni Randevu Al</a>
        </div>
    </div>
</body>
</html>
