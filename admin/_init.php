<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../lib/MailService.php';
require_once __DIR__ . '/../lib/SmsService.php';
require_once __DIR__ . '/../lib/Language.php';
require_once __DIR__ . '/../lib/BackupService.php';
require_once __DIR__ . '/../lib/CacheService.php';
require_once __DIR__ . '/../lib/ErrorHandler.php';
require_once __DIR__ . '/../api/ApiAuth.php';

Language::init($pdo);
Security::init($pdo);
Security::verifyAdminSession();

if (isset($_GET['lang'])) {
    Language::set($_GET['lang']);
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

$dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$services = $pdo->query("SELECT * FROM services ORDER BY id")->fetchAll();
$workingHours = $pdo->query("SELECT * FROM working_hours ORDER BY day_of_week")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY name")->fetchAll();

// Branch selection
if (isset($_GET['branch_id'])) {
    $_SESSION['admin_branch_id'] = (int)$_GET['branch_id'];
}
$currentBranchId = (int)($_SESSION['admin_branch_id'] ?? 0);
