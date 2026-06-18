        <!-- ─── TAB: MÜŞTERİLER ─── -->
        <?php
        $search = $_GET['q'] ?? '';
        $sql = "SELECT * FROM customers";
        $params = [];
        if ($search) {
            $sql .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
            $like = "%$search%";
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY last_visit DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        ?>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Müşteriler</h1>
        <form method="GET" class="mb-6">
            <input type="hidden" name="tab" value="customers">
            <div class="flex gap-2">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="İsim, e-posta veya telefon ile ara..." class="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                <button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Ara</button>
            </div>
        </form>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Müşteri</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">İletişim</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-600">Randevu</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-600">Harcama</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-600">Puan</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Son Ziyaret</th>
                </tr></thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Henüz müşteri bulunmuyor.</td></tr>
                    <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($c['name']) ?></td>
                        <td class="px-4 py-3">
                            <div class="text-gray-600"><?= htmlspecialchars($c['email']) ?></div>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($c['phone']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-center"><?= (int)$c['total_appointments'] ?></td>
                        <td class="px-4 py-3 text-center font-medium"><?= number_format($c['total_spent'], 0, ',', '.') ?> TL</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $c['loyalty_points'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= (int)$c['loyalty_points'] ?> Puan
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500"><?= $c['last_visit'] ? date('d.m.Y', strtotime($c['last_visit'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
