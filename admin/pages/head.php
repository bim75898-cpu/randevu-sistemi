<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Randevu Sistemi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="../pwa/manifest.json">
    <meta name="theme-color" content="<?= Settings::get('app_theme_color', '#4F46E5') ?>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .sidebar-link:hover { background: #eef2ff; color: #4f46e5; }
        .sidebar-link.active { background: #4f46e5; color: #fff; box-shadow: 0 4px 6px -1px rgba(79,70,229,0.3); }
        .sidebar-link.active svg { stroke: #fff; }
        .sidebar-link svg { stroke: #6b7280; flex-shrink: 0; }
        .sidebar-link:hover svg { stroke: #4f46e5; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #111827; }
        nav::-webkit-scrollbar { width: 4px; }
        nav::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
        nav::-webkit-scrollbar-track { background: transparent; }
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #sidebarOverlay { display: none; }
            #sidebarOverlay.open { display: block; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:sticky top-0 left-0 z-40 w-64 h-screen bg-white border-r border-gray-200 flex flex-col transition-transform duration-300">
        <!-- Brand -->
        <div class="flex items-center justify-between h-16 px-5 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Admin<span class="text-indigo-600">Panel</span></span>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Nav Items -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-1">
            <?php
            $navItems = [
                'dashboard' => ['Dashboard', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                'appointments' => ['Randevular', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                'services' => ['Hizmetler', 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z'],
                'employees' => ['Çalışanlar', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                'hours' => ['Çalışma Saatleri', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                'breaks' => ['Mola Zamanları', 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z'],
                'customers' => ['Müşteriler', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                'reviews' => ['Değerlendirmeler', 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                'packages' => ['Paketler', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                'series' => ['Tekrarlanan', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                'bulk' => ['Toplu Mesaj', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                'branches' => ['Şubeler', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                'api-test' => ['API Test', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                'settings' => ['Ayarlar', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
            foreach ($navItems as $t => $info):
                $isActive = $tab === $t;
            ?>
            <a href="<?= $t ?>.php" class="sidebar-link <?= $isActive ? 'active' : '' ?>" onclick="if(window.innerWidth<768)setTimeout(toggleSidebar,100)">
                <svg class="w-5 h-5 <?= $isActive ? 'text-white' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $info[1] ?>"/></svg>
                <span><?= $info[0] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Branch Selector -->
        <?php if (!empty($branches)): ?>
        <div class="flex-shrink-0 px-4 py-2 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">Şube</span>
                <select onchange="if(this.value)window.location.href='?branch_id='+this.value" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                    <option value="0">Tümü</option>
                    <?php foreach ($branches as $br): ?>
                    <option value="<?= $br['id'] ?>" <?= $currentBranchId === (int)$br['id'] ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <!-- Language Switcher -->
        <div class="flex-shrink-0 px-4 py-2 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400"><?= _t('language') ?></span>
                <div class="flex gap-1">
                    <a href="?lang=tr" class="px-2 py-1 text-xs rounded <?= Language::current() === 'tr' ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700' ?>">TR</a>
                    <a href="?lang=en" class="px-2 py-1 text-xs rounded <?= Language::current() === 'en' ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700' ?>">EN</a>
                </div>
            </div>
        </div>

        <!-- Bottom User Info -->
        <div class="flex-shrink-0 p-4 border-t border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm"><?= mb_substr($_SESSION['admin_username'] ?? 'A', 0, 1) ?></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($_SESSION['admin_username']) ?></p>
                    <p class="text-xs text-gray-400"><?= _t('admin_panel') ?></p>
                </div>
                <div class="flex gap-1">
                    <a href="../index.php" class="p-1.5 rounded-lg hover:bg-gray-100" title="<?= _t('site') ?>">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="logout.php" class="p-1.5 rounded-lg hover:bg-red-50" title="<?= _t('logout') ?>">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-h-screen overflow-x-hidden">
        <!-- Top Bar (Mobile) -->
        <div class="md:hidden flex items-center justify-between h-14 px-4 bg-white border-b border-gray-200 sticky top-0 z-20">
            <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="text-sm font-semibold text-gray-800"><?= $navItems[$tab][0] ?? 'AdminPanel' ?></span>
            <div class="w-5"></div>
        </div>

        <!-- Content Wrapper -->
        <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
