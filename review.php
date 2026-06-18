<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/Language.php';
Language::init($pdo);

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$appointment = null;
$alreadyReviewed = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT a.*, s.name as service_name FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.token = ?");
    $stmt->execute([$token]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $error = 'Geçersiz veya süresi dolmuş bağlantı.';
    } else {
        $check = $pdo->prepare("SELECT id FROM reviews WHERE appointment_id = ?");
        $check->execute([$appointment['id']]);
        $alreadyReviewed = (bool)$check->fetchColumn();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood_score']) && !$alreadyReviewed) {
        $mood = (int)$_POST['mood_score'];
        $comment = trim($_POST['comment'] ?? '');
        if ($mood >= 1 && $mood <= 5) {
            $ins = $pdo->prepare("INSERT INTO reviews (appointment_id, customer_id, mood_score, comment) VALUES (?, ?, ?, ?)");
            $ins->execute([$appointment['id'], $appointment['customer_id'], $mood, $comment]);
            $success = true;
        } else {
            $error = 'Lütfen bir puan seçin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Değerlendirmesi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <?php if (!$token): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Değerlendirme Bağlantısı Geçersiz</h1>
                    <p class="text-sm text-gray-500 mt-2">Bu sayfaya ulaşmak için size gönderilen bağlantıyı kullanın.</p>
                </div>
            <?php elseif ($success): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Teşekkür Ederiz!</h1>
                    <p class="text-sm text-gray-500 mt-2">Değerlendirmeniz başarıyla kaydedildi.</p>
                </div>
            <?php elseif ($alreadyReviewed): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Zaten Değerlendirilmiş</h1>
                    <p class="text-sm text-gray-500 mt-2">Bu randevu için zaten bir değerlendirme yapılmış. Tekrar teşekkürler!</p>
                </div>
            <?php elseif ($error): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Hata</h1>
                    <p class="text-sm text-gray-500 mt-2"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php else: ?>
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Deneyiminizi Değerlendirin</h1>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($appointment['service_name']) ?> randevunuz nasıldı?</p>
                </div>
                <form method="POST" class="space-y-6">
                    <div class="flex justify-center gap-3">
                        <?php for ($i = 1; $i <= 5; $i++):
                            $emojis = ['😞', '😐', '🙂', '😊', '🤩'];
                            $labels = ['Kötü', 'Orta', 'İyi', 'Çok İyi', 'Harika'];
                        ?>
                        <label class="flex flex-col items-center cursor-pointer group">
                            <input type="radio" name="mood_score" value="<?= $i ?>" class="sr-only peer">
                            <span class="text-4xl transition-all peer-checked:scale-125 peer-checked:drop-shadow-md group-hover:scale-110"><?= $emojis[$i-1] ?></span>
                            <span class="text-xs text-gray-400 mt-1 peer-checked:text-indigo-600 peer-checked:font-medium"><?= $labels[$i-1] ?></span>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Yorumunuz (isteğe bağlı)</label>
                        <textarea name="comment" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="Deneyiminizi kısaca anlatır mısınız?"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md">Gönder</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
