        <!-- ─── TAB: HİZMETLER ─── -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Hizmetler</h1>
            <button onclick="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Yeni Hizmet
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($services as $s): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all <?= $s['is_active'] ? '' : 'opacity-60' ?>">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    </div>
                    <form method="POST" class="inline">
                        <input type="hidden" name="svc_action" value="toggle">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="px-2 py-1 text-xs rounded-lg transition-colors <?= $s['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                            <?= $s['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </button>
                    </form>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1"><?= htmlspecialchars($s['name']) ?></h3>
                <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?= $s['duration'] ?> dk
                    </span>
                    <span class="font-bold text-indigo-600"><?= number_format($s['price'], 0, ',', '.') ?> TL</span>
                    <?php if ($s['requires_payment']): ?>
                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-xs rounded font-medium">Ödeme Gerekli</span>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <button onclick="editService(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>', <?= $s['duration'] ?>, <?= $s['price'] ?>, <?= (int)$s['requires_payment'] ?>)" class="flex-1 px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200 transition-colors">Düzenle</button>
                    <form method="POST" class="flex-1" onsubmit="return confirm('Bu hizmeti silmek istediğinize emin misiniz?')">
                        <input type="hidden" name="svc_action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="w-full px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs hover:bg-red-100 transition-colors">Sil</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Add/Edit Modal -->
        <div id="serviceModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden items-center justify-center flex">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-900" id="modalTitle">Yeni Hizmet Ekle</h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="svc_action" id="svc_action" value="add">
                    <input type="hidden" name="id" id="svc_id" value="0">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hizmet Adı</label>
                            <input type="text" name="name" id="svc_name" required maxlength="100" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Süre (dk)</label>
                                <input type="number" name="duration" id="svc_duration" required min="5" max="480" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fiyat (TL)</label>
                                <input type="number" name="price" id="svc_price" required min="0" step="0.01" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="requires_payment" id="svc_requires_payment" value="1" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                            <label for="svc_requires_payment" class="text-sm font-medium text-gray-700">Online ödeme gerektirsin</label>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-md">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Hizmet Ekle';
            document.getElementById('svc_action').value = 'add';
            document.getElementById('svc_id').value = '0';
            document.getElementById('svc_name').value = '';
            document.getElementById('svc_duration').value = '30';
            document.getElementById('svc_price').value = '';
            document.getElementById('svc_requires_payment').checked = false;
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        function editService(id, name, duration, price, requiresPayment) {
            document.getElementById('modalTitle').textContent = 'Hizmet Düzenle';
            document.getElementById('svc_action').value = 'edit';
            document.getElementById('svc_id').value = id;
            document.getElementById('svc_name').value = name;
            document.getElementById('svc_duration').value = duration;
            document.getElementById('svc_price').value = price;
            document.getElementById('svc_requires_payment').checked = requiresPayment === 1;
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('serviceModal').classList.add('hidden');
        }
        </script>
