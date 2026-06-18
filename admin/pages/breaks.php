        <!-- ─── TAB: MOLA ZAMANLARI ─── -->
        <?php
        $breakList = $pdo->query("SELECT bt.*, e.name as employee_name FROM break_times bt LEFT JOIN employees e ON bt.employee_id = e.id ORDER BY bt.day_of_week, bt.start_time")->fetchAll();
        $allEmps = $pdo->query("SELECT id, name FROM employees WHERE is_active = 1")->fetchAll();
        ?>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Mola Zamanları</h1>
        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div id="breakRows">
                <?php if (empty($breakList)): ?>
                <div class="flex items-center gap-2 mb-3 break-row">
                    <select name="breaks[0][day]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <?php foreach ($dayNames as $i => $dn): ?><option value="<?= $i ?>"><?= $dn ?></option><?php endforeach; ?>
                    </select>
                    <input type="time" name="breaks[0][start]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                    <span class="text-gray-300">—</span>
                    <input type="time" name="breaks[0][end]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                    <input type="text" name="breaks[0][label]" placeholder="Örn: Öğle Arası" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <select name="breaks[0][employee_id]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">Tüm Çalışanlar</option>
                        <?php foreach ($allEmps as $ae): ?><option value="<?= $ae['id'] ?>"><?= htmlspecialchars($ae['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" onclick="this.closest('.break-row').remove()" class="text-red-500 hover:text-red-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php else: ?>
                <?php foreach ($breakList as $i => $b): ?>
                <div class="flex items-center gap-2 mb-3 break-row">
                    <select name="breaks[<?= $i ?>][day]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <?php foreach ($dayNames as $di => $dn): ?><option value="<?= $di ?>" <?= $di === (int)$b['day_of_week'] ? 'selected' : '' ?>><?= $dn ?></option><?php endforeach; ?>
                    </select>
                    <input type="time" name="breaks[<?= $i ?>][start]" value="<?= date('H:i', strtotime($b['start_time'])) ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                    <span class="text-gray-300">—</span>
                    <input type="time" name="breaks[<?= $i ?>][end]" value="<?= date('H:i', strtotime($b['end_time'])) ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                    <input type="text" name="breaks[<?= $i ?>][label]" value="<?= htmlspecialchars($b['label'] ?? '') ?>" placeholder="Örn: Öğle Arası" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <select name="breaks[<?= $i ?>][employee_id]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">Tüm Çalışanlar</option>
                        <?php foreach ($allEmps as $ae): ?><option value="<?= $ae['id'] ?>" <?= $b['employee_id'] == $ae['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ae['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" onclick="this.closest('.break-row').remove()" class="text-red-500 hover:text-red-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addBreakRow()" class="mt-2 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">+ Mola Ekle</button>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <button type="submit" name="save_breaks" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-md">Kaydet</button>
            </div>
        </form>
        <script>
        let breakIdx = <?= count($breakList) ?>;
        function addBreakRow() {
            const html = `<div class="flex items-center gap-2 mb-3 break-row">
                <select name="breaks[${breakIdx}][day]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <?php foreach ($dayNames as $i => $dn): ?><option value="<?= $i ?>"><?= $dn ?></option><?php endforeach; ?>
                </select>
                <input type="time" name="breaks[${breakIdx}][start]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                <span class="text-gray-300">—</span>
                <input type="time" name="breaks[${breakIdx}][end]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                <input type="text" name="breaks[${breakIdx}][label]" placeholder="Örn: Öğle Arası" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <select name="breaks[${breakIdx}][employee_id]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option value="">Tüm Çalışanlar</option>
                    <?php foreach ($allEmps as $ae): ?><option value="<?= $ae['id'] ?>"><?= htmlspecialchars($ae['name']) ?></option><?php endforeach; ?>
                </select>
                <button type="button" onclick="this.closest('.break-row').remove()" class="text-red-500 hover:text-red-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>`;
            document.getElementById('breakRows').insertAdjacentHTML('beforeend', html);
            breakIdx++;
        }
        </script>
