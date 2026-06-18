        <!-- ─── TAB: RANDEVULAR ─── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4 sm:mb-0">Randevular</h1>
            <div class="flex space-x-2">
                <span class="px-3 py-1.5 bg-yellow-100 text-yellow-800 rounded-lg text-sm font-medium"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'pending')) ?> Bekleyen</span>
                <span class="px-3 py-1.5 bg-green-100 text-green-800 rounded-lg text-sm font-medium"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')) ?> Onaylı</span>
                <span class="px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'completed')) ?> Tamamlanan</span>
            </div>
        </div>

        <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Durum</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tümü</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                    <option value="confirmed" <?= ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Onaylı</option>
                    <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>İptal</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors">Filtrele</button>
            <a href="appointments.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition-colors">Sıfırla</a>
            <a href="../pdf.php?type=daily&date=<?= date('Y-m-d') ?>" target="_blank" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600 transition-colors">📄 Bugün PDF</a>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tarih</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Saat</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Müşteri</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">İletişim</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Hizmet</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Çalışan</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Tutar</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Ödeme</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Check-In</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Durum</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">İşlem</th>
                    </tr></thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="11" class="px-4 py-8 text-center text-gray-400">Henüz randevu bulunmuyor.</td></tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $a): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium"><?= date('d.m.Y', strtotime($a['appointment_date'])) ?></td>
                                <td class="px-4 py-3"><?= date('H:i', strtotime($a['appointment_time'])) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($a['customer_name']) ?></div>
                                    <?php if ($a['notes']): ?><div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($a['notes']) ?></div><?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-600"><?= htmlspecialchars($a['customer_email']) ?></div>
                                    <div class="text-gray-500 text-xs"><?= htmlspecialchars($a['customer_phone']) ?></div>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($a['service_name']) ?></td>
                                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($a['employee_name'] ?? '-') ?></td>
                                <td class="px-4 py-3 font-medium"><?= number_format($a['price'], 0, ',', '.') ?> TL</td>
                                <td class="px-4 py-3">
                                    <?php
                                    $payBadge = match($a['payment_status']) { 'paid' => 'bg-green-100 text-green-700', 'refunded' => 'bg-purple-100 text-purple-700', default => 'bg-gray-100 text-gray-500' };
                                    $payLabel = match($a['payment_status']) { 'paid' => 'Ödendi', 'refunded' => 'İade', default => 'Bekliyor' };
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?= $payBadge ?>"><?= $payLabel ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($a['checked_in_at']): ?>
                                    <span class="text-green-600 text-xs font-medium">✅ <?= date('H:i', strtotime($a['checked_in_at'])) ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-300 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $badge = match($a['status']) { 'pending' => 'bg-yellow-100 text-yellow-800', 'confirmed' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800', 'completed' => 'bg-blue-100 text-blue-800', default => 'bg-gray-100 text-gray-800' };
                                    $label = match($a['status']) { 'pending' => 'Bekliyor', 'confirmed' => 'Onaylı', 'cancelled' => 'İptal', 'completed' => 'Tamamlandı', default => $a['status'] };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $badge ?>"><?= $label ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-1">
                                        <?php if ($a['status'] === 'pending'): ?>
                                            <a href="?action=confirmed&id=<?= $a['id'] ?>" class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600">Onayla</a>
                                            <a href="?action=cancelled&id=<?= $a['id'] ?>" class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">İptal</a>
                                        <?php elseif ($a['status'] === 'confirmed'): ?>
                                            <a href="?action=completed&id=<?= $a['id'] ?>" class="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">Tamamla</a>
                                            <a href="?action=cancelled&id=<?= $a['id'] ?>" class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">İptal</a>
                                        <?php endif; ?>
                                        <a href="../pdf.php?type=appointment&id=<?= $a['id'] ?>" target="_blank" class="px-2 py-1 bg-gray-200 text-gray-600 rounded text-xs hover:bg-gray-300" title="PDF Fiş">📄</a>
                                        <?php if ($a['token']): ?>
                                        <a href="../checkin.php?token=<?= $a['token'] ?>" target="_blank" class="px-2 py-1 bg-indigo-100 text-indigo-600 rounded text-xs hover:bg-indigo-200" title="Check-In Linki">🚪</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
