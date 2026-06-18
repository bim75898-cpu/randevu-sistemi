<?php
require __DIR__ . '/_init.php';
$tab = 'reviews';

$moodFilter = $_GET['mood'] ?? '';
$sql = "SELECT r.*, a.customer_name, a.customer_email, a.appointment_date, s.name as service_name FROM reviews r JOIN appointments a ON r.appointment_id = a.id JOIN services s ON a.service_id = s.id";
$params = [];
if ($moodFilter) {
    $sql .= " WHERE r.mood_score = ?";
    $params[] = (int)$moodFilter;
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allReviews = $stmt->fetchAll();

require __DIR__ . '/pages/head.php';
?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Değerlendirmeler</h1>
    <div class="flex gap-2">
        <a href="reviews.php" class="px-3 py-1.5 text-xs rounded-lg <?= !$moodFilter ? 'bg-indigo-100 text-indigo-700 font-medium' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">Tümü</a>
        <?php $emojiMap = ['😞','😐','🙂','😊','🤩']; ?>
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <a href="?mood=<?= $i ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $moodFilter == $i ? 'bg-indigo-100 text-indigo-700 font-medium' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>"><?= $emojiMap[$i-1] ?> <?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (empty($allReviews)): ?>
    <p class="text-sm text-gray-400 text-center py-12">Henüz değerlendirme bulunmuyor.</p>
    <?php else: ?>
    <div class="divide-y divide-gray-50">
        <?php foreach ($allReviews as $rv): ?>
        <div class="p-4 flex items-start gap-4 hover:bg-gray-50 transition-colors">
            <span class="text-3xl"><?= $emojiMap[(int)$rv['mood_score']-1] ?></span>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($rv['customer_name']) ?></p>
                    <span class="text-xs text-gray-400"><?= date('d.m.Y H:i', strtotime($rv['created_at'])) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($rv['service_name']) ?> · <?= $rv['appointment_date'] ?></p>
                <?php if ($rv['comment']): ?>
                <p class="text-sm text-gray-600 mt-2 italic bg-gray-50 rounded-lg p-3">"<?= htmlspecialchars($rv['comment']) ?>"</p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-1">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="text-sm <?= $s <= $rv['mood_score'] ? '' : 'opacity-20' ?>">⭐</span>
                <?php endfor; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/pages/footer.php'; ?>
