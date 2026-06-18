<?php
require __DIR__ . '/_init.php';
$tab = 'employees';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empAction = $_POST['emp_action'] ?? '';
    $name = Security::sanitizeInput(trim($_POST['name'] ?? ''));
    $email = Security::sanitizeInput(trim($_POST['email'] ?? ''));
    $phone = Security::sanitizeInput(trim($_POST['phone'] ?? ''));
    $eid = (int)($_POST['id'] ?? 0);
    if ($empAction === 'add' && $name && $email) {
        $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $phone]);
        $newId = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO employee_hours (employee_id, day_of_week, is_open, open_time, close_time) SELECT $newId, day_of_week, is_open, open_time, close_time FROM working_hours");
    } elseif ($empAction === 'edit' && $eid && $name) {
        $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $eid]);
    } elseif ($empAction === 'toggle' && $eid) {
        $pdo->prepare("UPDATE employees SET is_active = NOT is_active WHERE id = ?")->execute([$eid]);
    } elseif ($empAction === 'hours' && $eid) {
        $days = $_POST['hours'] ?? [];
        foreach ($days as $dayId => $data) {
            $isOpen = !empty($data['is_open']) ? 1 : 0;
            $openTime = $data['open'] ?? '09:00';
            $closeTime = $data['close'] ?? '18:00';
            $stmt = $pdo->prepare("UPDATE employee_hours SET is_open = ?, open_time = ?, close_time = ? WHERE id = ? AND employee_id = ?");
            $stmt->execute([$isOpen, $openTime, $closeTime, (int)$dayId, $eid]);
        }
    }
    header('Location: employees.php');
    exit;
}

require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/employees.php'; ?>
<?php require __DIR__ . '/pages/footer.php'; ?>
