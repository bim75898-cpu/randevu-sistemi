<?php
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$todayAppointments = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
$todayAppointments->execute([$today]);
$todayAppointments = $todayAppointments->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.status != 'cancelled'")->fetchColumn();
$monthRevenue = $pdo->prepare("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.appointment_date >= ? AND a.status != 'cancelled'");
$monthRevenue->execute([$monthStart]);
$monthRevenue = $monthRevenue->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$employeeCount = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
$chartLabels = []; $chartAppts = []; $chartRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d.m', strtotime($d));
    $c = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
    $c->execute([$d]); $chartAppts[] = (int)$c->fetchColumn();
    $r = $pdo->prepare("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.appointment_date = ? AND a.status != 'cancelled'");
    $r->execute([$d]); $chartRevenue[] = (float)$r->fetchColumn();
}
$popular = $pdo->query("SELECT s.name, COUNT(*) as cnt FROM appointments a JOIN services s ON a.service_id = s.id GROUP BY a.service_id ORDER BY cnt DESC LIMIT 5")->fetchAll();
$empPerf = $pdo->query("SELECT e.name, COUNT(a.id) as cnt FROM employees e LEFT JOIN appointments a ON a.employee_id = e.id AND a.appointment_date >= '$monthStart' GROUP BY e.id ORDER BY cnt DESC")->fetchAll();
$avgMood = $pdo->query("SELECT COALESCE(AVG(r.mood_score),0) as avg_mood, COUNT(r.id) as total_reviews FROM reviews r")->fetch();
$moodDist = $pdo->query("SELECT mood_score, COUNT(*) as cnt FROM reviews GROUP BY mood_score ORDER BY mood_score")->fetchAll();
$moodLabels = []; $moodCounts = [];
for ($i = 1; $i <= 5; $i++) { $moodLabels[] = $i; $found = 0; foreach ($moodDist as $m) { if ((int)$m['mood_score'] === $i) { $found = (int)$m['cnt']; break; } } $moodCounts[] = $found; }
$heatmap = $pdo->query("SELECT DAYOFWEEK(appointment_date) as dow, HOUR(appointment_time) as hour, COUNT(*) as cnt FROM appointments WHERE status != 'cancelled' GROUP BY dow, hour ORDER BY dow, hour")->fetchAll();
$heatmapData = []; foreach ($heatmap as $h) { $heatmapData[$h['dow']][$h['hour']] = (int)$h['cnt']; }
$recentReviews = $pdo->query("SELECT r.*, a.customer_name, s.name as service_name FROM reviews r JOIN appointments a ON r.appointment_id = a.id JOIN services s ON a.service_id = s.id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();
?>
        <!-- ─── TAB: DASHBOARD ─── -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Bugün</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $todayAppointments ?></p>
                <p class="text-xs text-gray-400">randevu</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Toplam</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $totalAppointments ?></p>
                <p class="text-xs text-gray-400">randevu</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Bekleyen</p>
                <p class="text-2xl font-bold text-amber-600 mt-1"><?= $pendingCount ?></p>
                <p class="text-xs text-gray-400">onay bekliyor</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Bu Ay</p>
                <p class="text-2xl font-bold text-green-600 mt-1"><?= number_format($monthRevenue, 0, ',', '.') ?> TL</p>
                <p class="text-xs text-gray-400">ciro</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Son 7 Gün</h3>
                <canvas id="chartWeek" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Popüler Hizmetler</h3>
                <canvas id="chartServices" height="200"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Çalışan Performansı (Bu Ay)</h3>
                <canvas id="chartEmployees" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Özet</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Toplam Müşteri</span><span class="font-medium"><?= $customerCount ?></span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Aktif Çalışan</span><span class="font-medium"><?= $employeeCount ?></span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Toplam Ciro</span><span class="font-medium"><?= number_format($totalRevenue, 0, ',', '.') ?> TL</span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Aylık Ciro</span><span class="font-medium"><?= number_format($monthRevenue, 0, ',', '.') ?> TL</span></div>
                    <div class="pt-3 border-t border-gray-100">
                        <a href="appointments.php" class="text-sm text-indigo-600 hover:text-indigo-800">Tüm randevuları gör →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mood Tracker -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Müşteri Memnuniyeti</h3>
                    <span class="text-sm text-gray-400"><?= (int)$avgMood['total_reviews'] ?> değerlendirme</span>
                </div>
                <?php if ($avgMood['total_reviews'] > 0): ?>
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-4xl"><?= ['😞','😐','🙂','😊','🤩'][round($avgMood['avg_mood'])-1] ?></span>
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($avgMood['avg_mood'], 1) ?></p>
                        <p class="text-xs text-gray-400">ortalama puan</p>
                    </div>
                </div>
                <canvas id="chartMood" height="150"></canvas>
                <?php else: ?>
                <p class="text-sm text-gray-400 text-center py-8">Henüz değerlendirme yapılmadı.</p>
                <?php endif; ?>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Yoğunluk Isı Haritası</h3>
                <div class="overflow-x-auto">
                    <table class="text-xs w-full">
                        <thead><tr class="text-gray-400">
                            <th class="p-1"></th><th class="p-1 text-center">00</th><th class="p-1 text-center">01</th><th class="p-1 text-center">02</th><th class="p-1 text-center">03</th><th class="p-1 text-center">04</th><th class="p-1 text-center">05</th><th class="p-1 text-center">06</th><th class="p-1 text-center">07</th><th class="p-1 text-center">08</th><th class="p-1 text-center">09</th><th class="p-1 text-center">10</th><th class="p-1 text-center">11</th><th class="p-1 text-center">12</th><th class="p-1 text-center">13</th><th class="p-1 text-center">14</th><th class="p-1 text-center">15</th><th class="p-1 text-center">16</th><th class="p-1 text-center">17</th><th class="p-1 text-center">18</th><th class="p-1 text-center">19</th><th class="p-1 text-center">20</th><th class="p-1 text-center">21</th><th class="p-1 text-center">22</th><th class="p-1 text-center">23</th>
                        </tr></thead>
                        <tbody>
                            <?php $dayNames = ['','Paz','Pts','Sal','Çar','Per','Cum','Cmt']; ?>
                            <?php for ($d = 1; $d <= 7; $d++): ?>
                            <tr>
                                <td class="p-1 text-gray-400 font-medium"><?= $dayNames[$d] ?></td>
                                <?php for ($h = 0; $h <= 23; $h++):
                                    $cnt = $heatmapData[$d][$h] ?? 0;
                                    $maxVal = 1;
                                    foreach ($heatmapData as $day) { if (is_array($day)) $maxVal = max($maxVal, max($day)); }
                                    $pct = $maxVal > 0 ? $cnt / $maxVal : 0;
                                    $colors = ['bg-indigo-50','bg-indigo-100','bg-indigo-200','bg-indigo-300','bg-indigo-400','bg-indigo-500','bg-indigo-600'];
                                    $ci = (int)($pct * 6);
                                ?>
                                <td class="p-1 text-center <?= $colors[min($ci,6)] ?> <?= $cnt > 0 ? 'text-white text-[10px] font-bold' : 'text-transparent' ?>"><?= $cnt ?: '·' ?></td>
                                <?php endfor; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-gray-400 mt-2 text-center">Saat / Gün bazında randevu yoğunluğu</p>
            </div>
        </div>

        <!-- Son Değerlendirmeler -->
        <?php if (!empty($recentReviews)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Son Değerlendirmeler</h3>
                <a href="reviews.php" class="text-xs text-indigo-600 hover:underline">Tümünü Gör</a>
            </div>
            <div class="space-y-3">
                <?php $emojiMap = ['😞','😐','🙂','😊','🤩']; ?>
                <?php foreach ($recentReviews as $rv): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="text-2xl"><?= $emojiMap[(int)$rv['mood_score']-1] ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rv['customer_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($rv['service_name']) ?> · <?= date('d.m.Y', strtotime($rv['created_at'])) ?></p>
                        <?php if ($rv['comment']): ?><p class="text-xs text-gray-500 mt-1 italic">"<?= htmlspecialchars($rv['comment']) ?>"</p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
        new Chart(document.getElementById('chartWeek'), {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    { label: 'Randevu', data: <?= json_encode($chartAppts) ?>, borderColor: '#6366f1', tension: 0.3, fill: false },
                    { label: 'Ciro (TL)', data: <?= json_encode($chartRevenue) ?>, borderColor: '#22c55e', tension: 0.3, fill: false, yAxisID: 'y1' }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y1: { position: 'right', grid: { drawOnChartArea: false } } } }
        });
        new Chart(document.getElementById('chartServices'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($popular, 'name')) ?>,
                datasets: [{ data: <?= json_encode(array_column($popular, 'cnt')) ?>, backgroundColor: ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe'] }]
            }
        });
        new Chart(document.getElementById('chartEmployees'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($empPerf, 'name')) ?>,
                datasets: [{ label: 'Randevu', data: <?= json_encode(array_column($empPerf, 'cnt')) ?>, backgroundColor: '#6366f1' }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        <?php if ($avgMood['total_reviews'] > 0): ?>
        new Chart(document.getElementById('chartMood'), {
            type: 'bar',
            data: {
                labels: ['😞 1','😐 2','🙂 3','😊 4','🤩 5'],
                datasets: [{ label: 'Değerlendirme', data: <?= json_encode($moodCounts) ?>, backgroundColor: ['#f87171','#fbbf24','#a3e635','#34d399','#6366f1'], borderRadius: 4 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
        <?php endif; ?>
        </script>
