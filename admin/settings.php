<?php
require __DIR__ . '/_init.php';
$tab = 'settings';

if (isset($_GET['download'])) {
    BackupService::download($_GET['download']);
    exit;
}

if (isset($_POST['backup_action'])) {
    if ($_POST['backup_action'] === 'create') {
        $result = BackupService::create('manual');
        $_SESSION['flash'] = $result['success'] ? _t('backup_success') : $result['error'];
    } elseif ($_POST['backup_action'] === 'delete' && isset($_POST['file'])) {
        BackupService::delete($_POST['file']);
        $_SESSION['flash'] = _t('operation_success');
    }
    header('Location: settings.php#backup');
    exit;
}

if (isset($_POST['generate_api_key'])) {
    $newKey = \ApiAuth::generateKey();
    Settings::set('api_key', $newKey);
    $_SESSION['new_api_key'] = $newKey;
    header('Location: settings.php#api');
    exit;
}

if (isset($_POST['flush_cache'])) {
    $cache = CacheService::init();
    $cache->flush();
    $_SESSION['flash'] = _t('cache_cleared');
    header('Location: settings.php#cache');
    exit;
}

if (isset($_POST['error_log_action'])) {
    if ($_POST['error_log_action'] === 'view') {
        header('Content-Type: text/plain');
        echo ErrorHandler::getLog();
        exit;
    }
    if ($_POST['error_log_action'] === 'clear') {
        ErrorHandler::clearLog();
        $_SESSION['flash'] = _t('operation_success');
        header('Location: settings.php#error');
        exit;
    }
    if ($_POST['error_log_action'] === 'test_sentry') {
        $msg = ErrorHandler::sendTestSentry();
        $_SESSION['flash'] = $msg;
        header('Location: settings.php#error');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['backup_action']) && !isset($_POST['generate_api_key']) && !isset($_POST['flush_cache']) && !isset($_POST['error_log_action'])) {
    $keys = $_POST['settings'] ?? [];
    foreach ($keys as $key => $value) {
        Settings::set($key, $value);
    }
    $st = isset($_POST['tab_section']) ? '?st=' . urlencode($_POST['tab_section']) : '';
    header('Location: settings.php' . $st);
    exit;
}

$settingsPage = true;
require __DIR__ . '/pages/head.php';
?>
<?php require __DIR__ . '/pages/settings.php'; ?>

<form method="POST" id="formApiKey" style="display:none"><input type="hidden" name="generate_api_key" value="1"></form>
<form method="POST" id="formBackup" style="display:none"><input type="hidden" name="backup_action" value="create"></form>
<form method="POST" id="formErrorView" style="display:none"><input type="hidden" name="error_log_action" value="view"></form>
<form method="POST" id="formErrorTest" style="display:none"><input type="hidden" name="error_log_action" value="test_sentry"></form>
<form method="POST" id="formErrorClear" style="display:none"><input type="hidden" name="error_log_action" value="clear"></form>
<form method="POST" id="formFlushCache" style="display:none"><input type="hidden" name="flush_cache" value="1"></form>

<?php require __DIR__ . '/pages/footer.php'; ?>
