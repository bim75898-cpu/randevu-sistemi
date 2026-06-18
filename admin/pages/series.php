        <!-- ─── TAB: TEKRARLANAN RANDEVULAR ─── -->
        <?php $allServices = $pdo->query("SELECT * FROM services WHERE is_active = 1")->fetchAll();
        $allEmps = $pdo->query("SELECT * FROM employees WHERE is_active = 1")->fetchAll();
        $seriesList = $pdo->query("SELECT s.*, sv.name as service_name FROM appointment_series s JOIN services sv ON s.service_id = sv.id ORDER BY s.created_at DESC")->fetchAll();
        ?>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Tekrarlanan Randevular</h1>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="font-semibold text-gray-900 mb-4">Yeni Seri Oluştur</h2>
            <form method="POST">
                <input type="hidden" name="create_series" value="1">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hizmet</label>
                        <select name="service_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <?php foreach ($allServices as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Çalışan</label>
                        <select name="employee_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <option value="">Seçilmedi</option>
                            <?php foreach ($allEmps as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sıklık</label>
                        <select name="frequency" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <option value="weekly">Haftalık</option>
                            <option value="biweekly">2 Haftada Bir</option>
                            <option value="monthly">Aylık</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Müşteri Adı</label>
                        <input type="text" name="customer_name" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
                        <input type="email" name="customer_email" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                        <input type="tel" name="customer_phone" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Saat</label>
                        <input type="time" name="appointment_time" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Haftanın Günü</label>
                        <select name="day_of_week" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <?php foreach ($dayNames as $i => $dn): ?><option value="<?= $i ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $dn ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç</label>
                        <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş (opsiyonel)</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Not</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></textarea>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-md">Seriyi Oluştur</button>
            </form>
        </div>

        <?php if (!empty($seriesList)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Müşteri</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Hizmet</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Sıklık</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Saat</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Başlangıç</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Bitiş</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($seriesList as $sr): ?>
                    <tr class="border-b border-gray-50">
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($sr['customer_name']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($sr['service_name']) ?></td>
                        <td class="px-4 py-3"><?= ['weekly' => 'Haftalık', 'biweekly' => '2 Haftada Bir', 'monthly' => 'Aylık'][$sr['frequency']] ?></td>
                        <td class="px-4 py-3"><?= date('H:i', strtotime($sr['appointment_time'])) ?></td>
                        <td class="px-4 py-3"><?= date('d.m.Y', strtotime($sr['start_date'])) ?></td>
                        <td class="px-4 py-3"><?= $sr['end_date'] ? date('d.m.Y', strtotime($sr['end_date'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
