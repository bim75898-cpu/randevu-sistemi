<?php
require __DIR__ . '/_init.php';
$tab = 'branches';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = Security::sanitizeInput(trim($_POST['name'] ?? ''));
    $address = Security::sanitizeInput(trim($_POST['address'] ?? ''));
    $phone = Security::sanitizeInput(trim($_POST['phone'] ?? ''));
    $email = Security::sanitizeInput(trim($_POST['email'] ?? ''));
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'add' && $name) {
        $stmt = $pdo->prepare("INSERT INTO branches (name, address, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $address, $phone, $email]);
    } elseif ($action === 'edit' && $id && $name) {
        $stmt = $pdo->prepare("UPDATE branches SET name = ?, address = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $address, $phone, $email, $id]);
    } elseif ($action === 'toggle' && $id) {
        $pdo->prepare("UPDATE branches SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([$id]);
    }
    header('Location: branches.php');
    exit;
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY is_active DESC, name")->fetchAll();

require __DIR__ . '/pages/head.php';
?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Şubeler</h1>
    <button onclick="openModal('add')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">+ Yeni Şube</button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($branches as $b): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 <?= $b['is_active'] ? '' : 'opacity-60' ?>">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" name="action" value="toggle" class="text-xs px-2 py-1 rounded <?= $b['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $b['is_active'] ? 'Aktif' : 'Pasif' ?></button>
            </form>
        </div>
        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($b['name']) ?></h3>
        <?php if ($b['address']): ?><p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($b['address']) ?></p><?php endif; ?>
        <div class="flex gap-3 mt-3 text-xs text-gray-400">
            <?php if ($b['phone']): ?><span>📞 <?= htmlspecialchars($b['phone']) ?></span><?php endif; ?>
            <?php if ($b['email']): ?><span>✉ <?= htmlspecialchars($b['email']) ?></span><?php endif; ?>
        </div>
        <div class="flex gap-2 mt-4 pt-3 border-t border-gray-50">
            <button onclick="openModal('edit', <?= $b['id'] ?>, '<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['address'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['phone'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['email'], ENT_QUOTES) ?>')" class="text-xs text-indigo-600 hover:underline">Düzenle</button>
            <form method="POST" onsubmit="return confirm('Bu şubeyi silmek istediğinize emin misiniz?')" class="inline">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" name="action" value="delete" class="text-xs text-red-500 hover:underline">Sil</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="branchModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center" onclick="if(event.target===this)closeModal()">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
        <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Yeni Şube</h3>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId" value="0">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Şube Adı *</label><input type="text" name="name" id="modalName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Adres</label><textarea name="address" id="modalAddress" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label><input type="text" name="phone" id="modalPhone" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">E-posta</label><input type="email" name="email" id="modalEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">İptal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, id, name, address, phone, email) {
    document.getElementById('branchModal').classList.remove('hidden');
    document.getElementById('branchModal').classList.add('flex');
    document.getElementById('modalTitle').textContent = action === 'add' ? 'Yeni Şube' : 'Şube Düzenle';
    document.getElementById('modalAction').value = action;
    document.getElementById('modalId').value = id || 0;
    document.getElementById('modalName').value = name || '';
    document.getElementById('modalAddress').value = address || '';
    document.getElementById('modalPhone').value = phone || '';
    document.getElementById('modalEmail').value = email || '';
}
function closeModal() {
    document.getElementById('branchModal').classList.add('hidden');
    document.getElementById('branchModal').classList.remove('flex');
}
</script>
<?php require __DIR__ . '/pages/footer.php'; ?>
