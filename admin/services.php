<?php
require __DIR__ . '/_init.php';
$tab = 'services';

$branchFilter = $currentBranchId ? " WHERE branch_id = $currentBranchId OR branch_id IS NULL" : '';
$services = $pdo->query("SELECT * FROM services $branchFilter ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['svc_action'] ?? '';
    $name = Security::sanitizeInput(trim($_POST['name'] ?? ''));
    $duration = (int) ($_POST['duration'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $requiresPayment = isset($_POST['requires_payment']) ? 1 : 0;
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'add' && $name && $duration > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO services (name, duration, price, requires_payment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $duration, $price, $requiresPayment]);
    } elseif ($action === 'edit' && $id && $name && $duration > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE services SET name = ?, duration = ?, price = ?, requires_payment = ? WHERE id = ?");
        $stmt->execute([$name, $duration, $price, $requiresPayment, $id]);
    } elseif ($action === 'toggle' && $id) {
        $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete' && $id) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE service_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
        }
    }
    header('Location: services.php');
    exit;
}

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/services.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
