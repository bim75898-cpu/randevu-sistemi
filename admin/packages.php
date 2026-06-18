<?php
require __DIR__ . '/_init.php';
$tab = 'packages';

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $pid = (int)$_GET['json'];
    $pkg = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $pkg->execute([$pid]);
    $pkg = $pkg->fetch();
    if ($pkg) {
        $items = $pdo->prepare("SELECT * FROM package_items WHERE package_id = ?");
        $items->execute([$pid]);
        $pkg['items'] = $items->fetchAll();
    }
    echo json_encode($pkg ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = Security::sanitizeInput(trim($_POST['name'] ?? ''));
    $description = Security::sanitizeInput(trim($_POST['description'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $validDays = (int)($_POST['valid_days'] ?? 365);
    $serviceIds = $_POST['service_ids'] ?? [];
    $sessionCounts = $_POST['session_counts'] ?? [];
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'add' && $name && $price > 0 && !empty($serviceIds)) {
        $totalSessions = array_sum($sessionCounts);
        $stmt = $pdo->prepare("INSERT INTO packages (name, description, price, total_sessions, discount_percent, valid_days) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $totalSessions, $discount, $validDays]);
        $pid = $pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO package_items (package_id, service_id, sessions) VALUES (?, ?, ?)");
        foreach ($serviceIds as $i => $sid) {
            if ((int)$sid > 0) $ins->execute([$pid, (int)$sid, (int)($sessionCounts[$i] ?? 1)]);
        }
    } elseif ($action === 'edit' && $id && $name && $price > 0) {
        $totalSessions = array_sum($sessionCounts);
        $stmt = $pdo->prepare("UPDATE packages SET name=?, description=?, price=?, total_sessions=?, discount_percent=?, valid_days=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $totalSessions, $discount, $validDays, $id]);
        $pdo->prepare("DELETE FROM package_items WHERE package_id = ?")->execute([$id]);
        $ins = $pdo->prepare("INSERT INTO package_items (package_id, service_id, sessions) VALUES (?, ?, ?)");
        foreach ($serviceIds as $i => $sid) {
            if ((int)$sid > 0) $ins->execute([$id, (int)$sid, (int)($sessionCounts[$i] ?? 1)]);
        }
    } elseif ($action === 'toggle' && $id) {
        $pdo->prepare("UPDATE packages SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM packages WHERE id = ?")->execute([$id]);
    }
    header('Location: packages.php');
    exit;
}

$allPackages = $pdo->query("SELECT p.*, (SELECT GROUP_CONCAT(CONCAT(s.name,' x',pi.sessions) SEPARATOR ', ') FROM package_items pi JOIN services s ON pi.service_id = s.id WHERE pi.package_id = p.id) as items_str FROM packages p ORDER BY p.is_active DESC, p.name")->fetchAll();
$allServices = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name")->fetchAll();
$customerPacks = $pdo->query("SELECT cp.*, p.name as package_name, c.name as customer_name FROM customer_packages cp JOIN packages p ON cp.package_id = p.id JOIN customers c ON cp.customer_id = c.id ORDER BY cp.purchased_at DESC LIMIT 20")->fetchAll();

require __DIR__ . '/pages/head.php';
?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Paketler</h1>
    <button onclick="openPackageModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">+ Yeni Paket</button>
</div>

<?php if (!empty($customerPacks)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Son Satın Alınan Paketler</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-gray-50 border-b border-gray-100">
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Müşteri</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Paket</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">Kullanım</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">Durum</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Bitiş</th>
            </tr></thead>
            <tbody>
                <?php foreach ($customerPacks as $cp): ?>
                <?php $pct = $cp['sessions_total'] > 0 ? round($cp['sessions_used'] / $cp['sessions_total'] * 100) : 0; ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($cp['customer_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($cp['package_name']) ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center gap-2 justify-center">
                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full <?= $pct >= 100 ? 'bg-red-500' : 'bg-indigo-500' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500"><?= $cp['sessions_used'] ?>/<?= $cp['sessions_total'] ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $cp['status'] === 'active' ? 'bg-green-100 text-green-700' : ($cp['status'] === 'expired' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') ?>">
                            <?= $cp['status'] === 'active' ? 'Aktif' : ($cp['status'] === 'expired' ? 'Süresi Doldu' : 'İptal') ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= $cp['expires_at'] ? date('d.m.Y', strtotime($cp['expires_at'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($allPackages as $pkg): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden <?= $pkg['is_active'] ? '' : 'opacity-60' ?>">
        <div class="p-5">
            <div class="flex items-start justify-between mb-2">
                <h3 class="font-semibold text-gray-900 text-lg"><?= htmlspecialchars($pkg['name']) ?></h3>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                    <button type="submit" name="action" value="toggle" class="text-xs px-2 py-1 rounded <?= $pkg['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $pkg['is_active'] ? 'Aktif' : 'Pasif' ?></button>
                </form>
            </div>
            <div class="flex items-baseline gap-1 mb-3">
                <span class="text-2xl font-bold text-indigo-600"><?= number_format($pkg['price'], 0, ',', '.') ?> TL</span>
                <?php if ($pkg['discount_percent'] > 0): ?>
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">%<?= (int)$pkg['discount_percent'] ?> indirim</span>
                <?php endif; ?>
            </div>
            <?php if ($pkg['description']): ?><p class="text-xs text-gray-500 mb-3"><?= htmlspecialchars($pkg['description']) ?></p><?php endif; ?>
            <div class="text-xs text-gray-400 space-y-1">
                <span>📦 <?= $pkg['total_sessions'] ?> seans · ⏱ <?= $pkg['valid_days'] ?> gün geçerli</span>
                <?php if ($pkg['items_str']): ?><div class="mt-1">📋 <?= htmlspecialchars($pkg['items_str']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex gap-2">
            <button onclick="editPackage(<?= $pkg['id'] ?>)" class="text-xs text-indigo-600 hover:underline">Düzenle</button>
            <form method="POST" onsubmit="return confirm('Paketi sil?')" class="inline">
                <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                <button type="submit" name="action" value="delete" class="text-xs text-red-500 hover:underline">Sil</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Package Modal -->
<div id="pkgModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center" onclick="if(event.target===this)closePkgModal()">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <h3 id="pkgModalTitle" class="text-lg font-semibold text-gray-900 mb-4">Yeni Paket</h3>
        <form method="POST" id="pkgForm">
            <input type="hidden" name="action" id="pkgAction" value="add">
            <input type="hidden" name="id" id="pkgId" value="0">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Paket Adı *</label><input type="text" name="name" id="pkgName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label><textarea name="description" id="pkgDesc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea></div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fiyat (TL) *</label><input type="number" step="0.01" name="price" id="pkgPrice" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">İndirim %</label><input type="number" step="0.1" name="discount_percent" id="pkgDiscount" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Geçerlilik (gün)</label><input type="number" name="valid_days" id="pkgDays" value="365" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hizmetler</label>
                    <div id="pkgServices" class="space-y-2"></div>
                    <button type="button" onclick="addPkgServiceRow()" class="mt-2 text-xs text-indigo-600 hover:underline">+ Hizmet Ekle</button>
                </div>
            </div>
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="closePkgModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">İptal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
let pkgServiceIdx = 0;
const allServices = <?= json_encode(array_map(fn($s) => ['id' => $s['id'], 'name' => $s['name']], $allServices)) ?>;

function addPkgServiceRow(sid, sessions) {
    const container = document.getElementById('pkgServices');
    const opts = allServices.map(s => `<option value="${s.id}" ${sid == s.id ? 'selected' : ''}>${s.name}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-center';
    div.innerHTML = `<select name="service_ids[]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">${opts}</select>
        <input type="number" name="session_counts[]" value="${sessions || 1}" min="1" class="w-16 px-2 py-2 border border-gray-300 rounded-lg text-sm text-center">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm">✕</button>`;
    container.appendChild(div);
    pkgServiceIdx++;
}

function openPackageModal() {
    document.getElementById('pkgModalTitle').textContent = 'Yeni Paket';
    document.getElementById('pkgAction').value = 'add';
    document.getElementById('pkgId').value = '0';
    document.getElementById('pkgName').value = '';
    document.getElementById('pkgDesc').value = '';
    document.getElementById('pkgPrice').value = '';
    document.getElementById('pkgDiscount').value = '0';
    document.getElementById('pkgDays').value = '365';
    document.getElementById('pkgServices').innerHTML = '';
    addPkgServiceRow();
    document.getElementById('pkgModal').classList.remove('hidden');
    document.getElementById('pkgModal').classList.add('flex');
}

function editPackage(id) {
    fetch('packages.php?json=' + id)
    .then(r => r.json()).then(pkg => {
        document.getElementById('pkgModalTitle').textContent = 'Paket Düzenle';
        document.getElementById('pkgAction').value = 'edit';
        document.getElementById('pkgId').value = pkg.id;
        document.getElementById('pkgName').value = pkg.name;
        document.getElementById('pkgDesc').value = pkg.description || '';
        document.getElementById('pkgPrice').value = pkg.price;
        document.getElementById('pkgDiscount').value = pkg.discount_percent || 0;
        document.getElementById('pkgDays').value = pkg.valid_days || 365;
        document.getElementById('pkgServices').innerHTML = '';
        if (pkg.items) pkg.items.forEach(item => addPkgServiceRow(item.service_id, item.sessions));
        if (!pkg.items || !pkg.items.length) addPkgServiceRow();
        document.getElementById('pkgModal').classList.remove('hidden');
        document.getElementById('pkgModal').classList.add('flex');
    });
}

function closePkgModal() {
    document.getElementById('pkgModal').classList.add('hidden');
    document.getElementById('pkgModal').classList.remove('flex');
}
</script>
<?php require __DIR__ . '/pages/footer.php'; ?>
