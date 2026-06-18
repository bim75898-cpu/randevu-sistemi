<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
Security::init($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('csrf', 'CSRF token validation failed on login');
        Security::logLoginAttempt($_POST['username'] ?? 'unknown', false);
        $error = 'Güvenlik doğrulaması başarısız.';
    } elseif (!Security::checkBruteForce()) {
        $error = 'Çok fazla hatalı giriş. Hesabınız geçici olarak bloke edildi.';
    } else {
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre gereklidir.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                Security::logLoginAttempt($username, true);
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_ip'] = Security::getClientIP();
                $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['_created'] = time();
                header('Location: dashboard.php');
                exit;
            }

            Security::logLoginAttempt($username, false);
            Security::logSecurityEvent('failed_login', "Failed login for: $username");
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Paneli</h1>
            <p class="text-gray-500 mt-1">Devam etmek için giriş yapın</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
            <?= Security::getHoneypotField() ?>
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kullanıcı Adı</label>
                <input type="text" name="username" maxlength="50" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-700 transition-all focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-100">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Şifre</label>
                <input type="password" name="password" maxlength="128" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-700 transition-all focus:outline-none focus:border-indigo-500 focus:ring-3 focus:ring-indigo-100">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition-all shadow-lg">Giriş Yap</button>
        </form>

        <div class="text-center mt-6">
            <a href="../index.php" class="text-sm text-gray-400 hover:text-indigo-600 transition-colors">← Randevu Sayfasına Dön</a>
        </div>
    </div>
</body>
</html>
