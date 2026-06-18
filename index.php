<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
Security::init($pdo);

$stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id");
$services = $stmt->fetchAll();
$employees = $pdo->query("SELECT * FROM employees WHERE is_active = 1 ORDER BY name")->fetchAll();

$csrf_token = Security::generateCSRFToken();
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

// Sanitize messages for display
if ($success) $success = Security::sanitizeInput($success);
if ($error) $error = Security::sanitizeInput($error);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Sistemi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="pwa/manifest.json">
    <meta name="theme-color" content="<?= Settings::get('app_theme_color', '#4F46E5') ?>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .slide-down { animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .input-group:focus-within .input-icon { color: #6366f1; }
        .input-group:focus-within .input-border { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        .dropdown-menu { animation: dropdownIn 0.2s ease-out; transform-origin: top center; }
        @keyframes dropdownIn { from { opacity: 0; transform: scaleY(0.95) translateY(-5px); } to { opacity: 1; transform: scaleY(1) translateY(0); } }
        .dropdown-menu::-webkit-scrollbar { width: 4px; }
        .dropdown-menu::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .dropdown-menu::-webkit-scrollbar-track { background: transparent; }
        .time-slot { transition: all 0.2s ease; backdrop-filter: blur(4px); }
        .time-slot:hover:not(.disabled) { transform: translateY(-2px) scale(1.03); box-shadow: 0 8px 20px rgba(99,102,241,0.2); }
        .time-slot.selected { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-color: transparent; box-shadow: 0 4px 14px rgba(99,102,241,0.35); }
        .time-slot.disabled { opacity: 0.35; cursor: not-allowed; text-decoration: line-through; }
        input[type="date"]::-webkit-calendar-picker-indicator { opacity: 0.5; padding: 4px; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
        input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; background: rgba(99,102,241,0.1); }
        .float-label label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
        .float-label:focus-within label { color: #6366f1; }
        .input-icon { top: 50% !important; transform: translateY(-50%) !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Randevu<span class="text-indigo-600">Sistemi</span></span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="packages.php" class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Paketler</a>
                    <a href="customer/history.php" class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Geçmişim</a>
                    <a href="admin/" class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="text-center mb-10 fade-in">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">Randevu Al</h1>
            <p class="text-lg text-gray-500 max-w-xl mx-auto">Size en uygun zamanı seçin, randevunuzu hemen oluşturalım.</p>
        </div>

        <?php if ($success): ?>
            <div class="slide-down mb-6 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <div class="mt-3 pt-3 border-t border-green-200 flex flex-wrap gap-2">
                    <a href="customer/history.php" class="text-xs font-medium text-green-700 hover:text-green-900 transition-colors">Randevu Geçmişim →</a>
                    <a href="customer/cancel.php?token=<?= htmlspecialchars($_GET['token'] ?? '') ?>" class="text-xs font-medium text-green-700 hover:text-green-900 transition-colors <?= empty($_GET['token']) ? 'hidden' : '' ?>">Randevuyu Yönet →</a>
                    <a href="customer/pay.php?token=<?= htmlspecialchars($_GET['token'] ?? '') ?>" class="text-xs font-medium text-amber-600 hover:text-amber-800 transition-colors <?= empty($_GET['token']) ? 'hidden' : '' ?>">Ödeme Yap →</a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="slide-down mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl flex items-center space-x-3">
                <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sm:p-8 fade-in">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <?= Security::getHoneypotField() ?>

            <div class="space-y-5">
                <div class="input-group relative" id="service-dropdown">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Hizmet Seçin
                    </label>
                    <div class="relative">
                        <button type="button" id="dropdown-trigger" class="dropdown-trigger w-full flex items-center gap-3 px-4 py-3.5 border border-gray-200 rounded-xl text-left transition-all bg-white hover:border-indigo-300 focus:outline-none input-border cursor-pointer">
                            <div class="flex-1 min-w-0">
                                <span id="dropdown-selected-text" class="block text-gray-400 truncate">Bir hizmet seçin</span>
                                <span id="dropdown-selected-sub" class="block text-xs text-gray-400 mt-0.5 hidden"></span>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transition-transform dropdown-arrow shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="dropdown-menu" class="dropdown-menu absolute left-0 right-0 mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl z-50 hidden overflow-hidden" style="max-height:320px;overflow-y:auto">
                            <div class="py-1">
                                <?php foreach ($services as $service): ?>
                                <button type="button" class="dropdown-item w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-indigo-50 transition-colors border-b border-gray-50 last:border-0" data-value="<?= (int)$service['id'] ?>" data-duration="<?= (int)$service['duration'] ?>" data-name="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= number_format($service['price'], 0, ',', '.') ?>">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="block text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="block text-xs text-gray-500 mt-0.5"><?= (int)$service['duration'] ?> dakika</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="block text-sm font-bold text-indigo-600"><?= number_format($service['price'], 0, ',', '.') ?> TL</span>
                                        <?php if ($service['requires_payment']): ?>
                                            <span class="block text-xs text-amber-600 mt-0.5">Kart ile ödenir</span>
                                        <?php endif; ?>
                                    </div>
                                    <svg class="w-5 h-5 text-indigo-600 dropdown-check hidden shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <select name="service_id" id="service_id" class="hidden" required>
                            <option value="">Seçin</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int)$service['id'] ?>"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!empty($employees)): ?>
                <div class="input-group" id="employee-group" style="display:none">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Çalışan Seçin
                    </label>
                    <select name="employee_id" id="employee_id" class="w-full px-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none focus:border-indigo-500">
                        <option value="">Farketmez</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="input-group">
                        <label class="float-label text-gray-400 uppercase tracking-wide">Adınız Soyadınız</label>
                        <div class="relative">
                            <span class="input-icon absolute left-4 text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <input type="text" name="customer_name" required maxlength="100" placeholder="Ad Soyad" class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none input-border">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="float-label text-gray-400 uppercase tracking-wide">E-posta</label>
                        <div class="relative">
                            <span class="input-icon absolute left-4 text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input type="email" name="customer_email" required maxlength="254" placeholder="ornek@email.com" class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none input-border">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="float-label text-gray-400 uppercase tracking-wide">Telefon</label>
                        <div class="relative">
                            <span class="input-icon absolute left-4 text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </span>
                            <input type="tel" name="customer_phone" required maxlength="20" placeholder="05XX XXX XX XX" class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none input-border">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="float-label text-gray-400 uppercase tracking-wide">Tarih</label>
                        <div class="relative">
                            <span class="input-icon absolute left-4 text-gray-400 transition-colors pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <input type="date" name="appointment_date" id="appointment_date" required class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none input-border">
                        </div>
                    </div>
                </div>

                <div class="input-group relative">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Uygun Saatler
                    </label>
                    <div id="time_slots" class="min-h-[56px]">
                        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-gray-400 text-sm">Önce bir hizmet ve tarih seçin.</p>
                        </div>
                    </div>
                    <input type="hidden" name="appointment_time" id="appointment_time">
                </div>

                <div class="input-group">
                    <label class="float-label text-gray-400 uppercase tracking-wide">Not (İsteğe Bağlı)</label>
                    <div class="relative">
                        <span class="input-icon absolute left-4 text-gray-400 transition-colors pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </span>
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Eklemek istedikleriniz..." class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl text-gray-700 transition-all focus:outline-none input-border resize-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <button type="submit" class="group relative w-full overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-4 px-6 rounded-xl font-bold text-lg tracking-wide transition-all duration-300 shadow-lg hover:shadow-xl hover:from-indigo-700 hover:to-violet-700 active:scale-[0.98]">
                    <span class="relative z-10 flex items-center justify-center gap-3">
                        <svg class="w-5 h-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Randevu Oluştur
                    </span>
                    <div class="absolute inset-0 -translate-x-full group-hover:translate-x-0 transition-transform duration-500 bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                </button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Custom Dropdown
        const trigger = document.getElementById('dropdown-trigger');
        const menu = document.getElementById('dropdown-menu');
        const items = document.querySelectorAll('.dropdown-item');
        const hiddenSelect = document.getElementById('service_id');
        const selectedText = document.getElementById('dropdown-selected-text');
        const selectedSub = document.getElementById('dropdown-selected-sub');
        const arrow = document.querySelector('.dropdown-arrow');
        let isOpen = false;

        function closeDropdown() {
            isOpen = false;
            menu.classList.add('hidden');
            menu.classList.remove('slide-down');
            arrow.style.transform = 'rotate(0deg)';
        }

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            isOpen = !isOpen;
            if (isOpen) {
                menu.classList.remove('hidden');
                menu.classList.add('slide-down');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                closeDropdown();
            }
        });

        items.forEach(function(item) {
            item.addEventListener('click', function() {
                const value = this.dataset.value;
                const name = this.dataset.name;
                const price = this.dataset.price;
                const duration = this.dataset.duration;

                hiddenSelect.value = value;
                selectedText.textContent = name;
                selectedText.className = 'block text-sm font-medium text-gray-900 truncate';
                selectedSub.textContent = price + ' TL · ' + duration + ' dk';
                selectedSub.className = 'block text-xs text-indigo-600 mt-0.5';

                items.forEach(function(i) {
                    i.classList.remove('bg-indigo-50');
                    i.querySelector('.dropdown-check').classList.add('hidden');
                });
                this.classList.add('bg-indigo-50');
                this.querySelector('.dropdown-check').classList.remove('hidden');

                closeDropdown();
                hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.addEventListener('click', function(e) {
            if (isOpen && !document.getElementById('service-dropdown').contains(e.target)) {
                closeDropdown();
            }
        });

        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        const serviceSelect = document.getElementById('service_id');
        const dateInput = document.getElementById('appointment_date');
        const timeSlotsDiv = document.getElementById('time_slots');
        const timeInput = document.getElementById('appointment_time');
        const employeeSelect = document.getElementById('employee_id');
        const employeeGroup = document.getElementById('employee-group');

        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        function emptyState() {
            return '<div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-dashed border-gray-200"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-gray-400 text-sm">Önce bir hizmet ve tarih seçin.</p></div>';
        }

        function loadingState() {
            return '<div class="flex items-center justify-center gap-3 px-4 py-4 bg-gray-50 rounded-xl border border-dashed border-gray-200"><svg class="animate-spin h-5 w-5 text-indigo-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg><span class="text-gray-500 text-sm">Yükleniyor...</span></div>';
        }

        function loadTimeSlots() {
            const serviceId = serviceSelect.value;
            const date = dateInput.value;

            if (!serviceId || !date) {
                timeSlotsDiv.innerHTML = emptyState();
                timeInput.value = '';
                return;
            }

            timeSlotsDiv.innerHTML = loadingState();

            let url = 'get_times.php?service_id=' + encodeURIComponent(serviceId) + '&date=' + encodeURIComponent(date);
            if (employeeSelect && employeeSelect.value) {
                url += '&employee_id=' + encodeURIComponent(employeeSelect.value);
            }

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        timeSlotsDiv.innerHTML = '<div class="flex items-center gap-3 px-4 py-3 bg-red-50 rounded-xl border border-red-200"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-red-500 text-sm">' + data.error + '</p></div>';
                        return;
                    }
                    if (data.slots.length === 0) {
                        timeSlotsDiv.innerHTML = '<div class="flex items-center gap-3 px-4 py-3 bg-amber-50 rounded-xl border border-amber-200"><svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg><p class="text-amber-700 text-sm">Bu tarih için uygun saat bulunamadı.</p></div>';
                        return;
                    }
                    let html = '<div class="grid grid-cols-4 sm:grid-cols-6 gap-2.5">';
                    data.slots.forEach(slot => {
                        const disabled = !slot.available ? 'disabled' : '';
                        html += '<button type="button" class="time-slot ' + disabled + ' relative px-3 py-2.5 text-sm font-medium border border-gray-200 rounded-xl text-gray-700 hover:border-indigo-400 transition-all text-center ' + disabled + '" data-time="' + slot.time + '">' + slot.time + '</button>';
                    });
                    html += '</div>';
                    timeSlotsDiv.innerHTML = html;

                    document.querySelectorAll('.time-slot:not(.disabled)').forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot').forEach(b => b.classList.remove('selected'));
                            this.classList.add('selected');
                            timeInput.value = this.dataset.time;
                        });
                    });
                })
                .catch(() => {
                    timeSlotsDiv.innerHTML = '<div class="flex items-center gap-3 px-4 py-3 bg-red-50 rounded-xl border border-red-200"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-red-500 text-sm">Saatler yüklenirken bir hata oluştu.</p></div>';
                });
        }

        serviceSelect.addEventListener('change', function() {
            if (employeeGroup) employeeGroup.style.display = this.value ? 'block' : 'none';
            loadTimeSlots();
        });
        dateInput.addEventListener('change', loadTimeSlots);
        if (employeeSelect) employeeSelect.addEventListener('change', loadTimeSlots);
    });
    </script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('pwa/service-worker.js');
}
</script>
</body>
</html>
