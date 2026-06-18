        <!-- ─── TAB: ÇALIŞANLAR ─── -->
        <?php $employees = $pdo->query("SELECT * FROM employees ORDER BY id")->fetchAll(); ?>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Çalışanlar</h1>
            <button onclick="openEmpModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-md">+ Çalışan Ekle</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($employees as $e): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 <?= $e['is_active'] ? '' : 'opacity-60' ?>">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold"><?= mb_substr($e['name'], 0, 1) ?></div>
                        <div>
                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($e['name']) ?></h3>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($e['email']) ?> • <?= htmlspecialchars($e['phone']) ?></p>
                        </div>
                    </div>
                    <form method="POST" class="inline">
                        <input type="hidden" name="emp_action" value="toggle">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="px-2 py-1 text-xs rounded-lg <?= $e['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $e['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </button>
                    </form>
                </div>

                <?php
                $eHours = $pdo->prepare("SELECT * FROM employee_hours WHERE employee_id = ? ORDER BY day_of_week");
                $eHours->execute([$e['id']]);
                $hoursData = $eHours->fetchAll();
                ?>
                <details class="mt-3">
                    <summary class="text-sm text-indigo-600 cursor-pointer hover:text-indigo-800 font-medium">Çalışma Saatlerini Düzenle</summary>
                    <form method="POST" class="mt-3 space-y-2">
                        <input type="hidden" name="emp_action" value="hours">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <?php foreach ($hoursData as $h):
                            $hOpen = $h['open_time'] ? date('H:i', strtotime($h['open_time'])) : '09:00';
                            $hClose = $h['close_time'] ? date('H:i', strtotime($h['close_time'])) : '18:00';
                        ?>
                        <div class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="hours[<?= $h['id'] ?>][is_open]" value="1" <?= $h['is_open'] ? 'checked' : '' ?> class="w-3.5 h-3.5 text-indigo-600">
                            <span class="w-20 text-gray-600"><?= $dayNames[$h['day_of_week']] ?></span>
                            <input type="time" name="hours[<?= $h['id'] ?>][open]" value="<?= $hOpen ?>" class="px-2 py-1 border border-gray-200 rounded text-xs">
                            <span class="text-gray-300">—</span>
                            <input type="time" name="hours[<?= $h['id'] ?>][close]" value="<?= $hClose ?>" class="px-2 py-1 border border-gray-200 rounded text-xs">
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs mt-2 hover:bg-indigo-700">Saatleri Kaydet</button>
                    </form>
                </details>

                <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                    <button onclick="editEmp(<?= $e['id'] ?>, '<?= htmlspecialchars($e['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($e['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($e['phone'], ENT_QUOTES) ?>')" class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200">Düzenle</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Employee Modal -->
        <div id="empModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center flex">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                <form method="POST">
                    <input type="hidden" name="emp_action" id="emp_action" value="add">
                    <input type="hidden" name="id" id="emp_id" value="0">
                    <h2 class="text-lg font-bold text-gray-900 mb-4" id="empModalTitle">Çalışan Ekle</h2>
                    <div class="space-y-3">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label><input type="text" name="name" id="emp_name" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">E-posta</label><input type="email" name="email" id="emp_email" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label><input type="tel" name="phone" id="emp_phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm"></div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeEmpModal()" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-md">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function openEmpModal() {
            document.getElementById('empModalTitle').textContent = 'Çalışan Ekle';
            document.getElementById('emp_action').value = 'add';
            document.getElementById('emp_id').value = '0';
            document.getElementById('emp_name').value = '';
            document.getElementById('emp_email').value = '';
            document.getElementById('emp_phone').value = '';
            document.getElementById('empModal').classList.remove('hidden');
        }
        function editEmp(id, name, email, phone) {
            document.getElementById('empModalTitle').textContent = 'Çalışan Düzenle';
            document.getElementById('emp_action').value = 'edit';
            document.getElementById('emp_id').value = id;
            document.getElementById('emp_name').value = name;
            document.getElementById('emp_email').value = email;
            document.getElementById('emp_phone').value = phone;
            document.getElementById('empModal').classList.remove('hidden');
        }
        function closeEmpModal() { document.getElementById('empModal').classList.add('hidden'); }
        </script>
