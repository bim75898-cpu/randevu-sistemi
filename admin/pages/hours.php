        <!-- ─── TAB: ÇALIŞMA SAATLERİ ─── -->
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Çalışma Saatleri</h1>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <?php foreach ($workingHours as $wh):
                    $dayId = (int)$wh['day_of_week'];
                    $isOpen = (bool)$wh['is_open'];
                    $open = $wh['open_time'] ? date('H:i', strtotime($wh['open_time'])) : '09:00';
                    $close = $wh['close_time'] ? date('H:i', strtotime($wh['close_time'])) : '18:00';
                    $isToday = $dayId === (int)date('w');
                ?>
                <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors <?= $isToday ? 'bg-indigo-50/50' : '' ?>">
                    <div class="w-8 text-center">
                        <input type="checkbox" name="hours[<?= $wh['id'] ?>][is_open]" value="1" <?= $isOpen ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer">
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-gray-900"><?= $dayNames[$dayId] ?></span>
                        <?php if ($isToday): ?>
                            <span class="ml-2 px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded">Bugün</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <input type="time" name="hours[<?= $wh['id'] ?>][open]" value="<?= $open ?>" <?= $isOpen ? '' : 'disabled' ?> class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <span class="text-gray-300">—</span>
                        <div class="flex items-center gap-2">
                            <input type="time" name="hours[<?= $wh['id'] ?>][close]" value="<?= $close ?>" <?= $isOpen ? '' : 'disabled' ?> class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg">Kaydet</button>
            </div>
        </form>

        <script>
        document.querySelectorAll('[name^="hours"][name$="[is_open]"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var row = this.closest('.divide-y > div');
                var timeInputs = row.querySelectorAll('input[type="time"]');
                timeInputs.forEach(function(inp) { inp.disabled = !cb.checked; });
            });
        });
        </script>
