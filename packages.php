<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
Security::init($pdo);

$allPackages = $pdo->query("SELECT p.*, GROUP_CONCAT(CONCAT(s.name, ' x', pi.sessions) SEPARATOR ', ') as items_str FROM packages p JOIN package_items pi ON pi.package_id = p.id JOIN services s ON pi.service_id = s.id WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.price")->fetchAll();

$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paketler - Randevu Sistemi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }.fade-in { animation: fadeIn 0.5s ease-out; }@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Paketler</span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="index.php" class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">← Randevu Al</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="text-center mb-10 fade-in">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">Paketler</h1>
            <p class="text-lg text-gray-500 max-w-xl mx-auto">Birden fazla randevu alacaksanız paketlerle avantajlı fiyatlardan yararlanın.</p>
        </div>

        <?php if ($success): ?>
        <div class="slide-down mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in">
            <?php foreach ($allPackages as $pkg):
                $normalPrice = $pkg['total_sessions'] > 0 ? $pkg['price'] / (1 - ($pkg['discount_percent'] / 100)) : 0;
            ?>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($pkg['name']) ?></h3>
                    <?php if ($pkg['description']): ?><p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($pkg['description']) ?></p><?php endif; ?>
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-indigo-600"><?= number_format($pkg['price'], 0, ',', '.') ?> TL</span>
                        <?php if ($pkg['discount_percent'] > 0): ?>
                        <span class="text-sm line-through text-gray-400 ml-2"><?= number_format($normalPrice, 0, ',', '.') ?> TL</span>
                        <span class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">%<?= (int)$pkg['discount_percent'] ?> indirim</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-gray-500 space-y-2 mb-6">
                        <div class="flex items-center gap-2">📦 <span><?= $pkg['total_sessions'] ?> seans</span></div>
                        <div class="flex items-center gap-2">⏱ <span><?= $pkg['valid_days'] ?> gün geçerli</span></div>
                        <?php if ($pkg['items_str']): ?>
                        <div class="flex items-start gap-2">📋 <span><?= htmlspecialchars($pkg['items_str']) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="purchase_package.php" onsubmit="return confirm('Bu paketi satın almak istediğinize emin misiniz?')">
                        <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <?= Security::getHoneypotField() ?>
                        <div class="space-y-2 mb-4">
                            <input type="text" name="customer_name" placeholder="Adınız Soyadınız" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <input type="email" name="customer_email" placeholder="E-posta adresiniz" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <input type="tel" name="customer_phone" placeholder="Telefon numaranız" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <button type="submit" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md">Satın Al</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($allPackages)): ?>
            <div class="col-span-full text-center py-16">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-400 text-lg">Henüz aktif paket bulunmuyor.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('pwa/service-worker.js');
    }
    </script>
</body>
</html>
