        <!-- ─── TAB: AYARLAR ─── -->
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Sistem Ayarları</h1>

        <?php
        $allSettings = Settings::getAll();
        $smtpOk = !empty($allSettings['mail_smtp_username'] ?? '');
        $twilioOk = !empty($allSettings['sms_twilio_sid'] ?? '');
        $netgsmOk = !empty($allSettings['sms_netgsm_user'] ?? '');
        $paymentOk = !empty($allSettings['payment_iyzico_api_key'] ?? '') && $allSettings['payment_iyzico_api_key'] !== 'sandbox-';
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?= $smtpOk ? 'bg-green-100' : 'bg-amber-100' ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $smtpOk ? 'text-green-600' : 'text-amber-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">E-posta (SMTP)</h3>
                        <p class="text-xs text-gray-500"><?= $smtpOk ? 'Yapılandırılmış' : 'Yapılandırılmamış' ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?= ($twilioOk || $netgsmOk) ? 'bg-green-100' : 'bg-amber-100' ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= ($twilioOk || $netgsmOk) ? 'text-green-600' : 'text-amber-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">SMS Servisi</h3>
                        <p class="text-xs text-gray-500">Şu an: <strong><?= $allSettings['sms_provider'] ?? 'log' ?></strong> modunda</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?= $paymentOk ? 'bg-green-100' : 'bg-amber-100' ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $paymentOk ? 'text-green-600' : 'text-amber-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Online Ödeme</h3>
                        <p class="text-xs text-gray-500"><?= $allSettings['payment_iyzico_sandbox'] === '1' ? 'Sandbox (test)' : 'Canlı' ?> modu</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Güvenlik</h3>
                        <p class="text-xs text-gray-500">Tüm korumalar aktif</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Sub-Tabs -->
        <div id="settingsApp">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex overflow-x-auto border-b border-gray-200 bg-gray-50/50" id="settingsTabs">
                    <?php
                    $settingsTabs = [
                        'email' => ['📧', 'E-posta'],
                        'sms' => ['📱', 'SMS'],
                        'payment' => ['💳', 'Ödeme'],
                        'lang' => ['🌐', 'Dil & PWA'],
                        'api' => ['🔌', 'API'],
                        'backup' => ['💾', 'Yedekleme'],
                        'error' => ['⚠️', 'Hata'],
                        'cache' => ['⚡', 'Önbellek'],
                    ];
                    $activeSettingsTab = $_GET['st'] ?? 'email';
                    foreach ($settingsTabs as $key => $info):
                        $isActive = $key === $activeSettingsTab;
                    ?>
                    <a href="?st=<?= $key ?>" class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-all <?= $isActive ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        <span><?= $info[0] ?></span>
                        <span><?= $info[1] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- ALL SETTINGS FIELDS in one form, only the active tab is visible -->
                <form method="POST" id="settingsForm">
                    <input type="hidden" name="tab_section" value="<?= $activeSettingsTab ?>">

                    <!-- ─── TAB: E-POSTA ─── -->
                    <div class="settings-panel" data-tab="email" style="display:<?= $activeSettingsTab === 'email' ? 'block' : 'none' ?>">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">SMTP Sunucu</label><input type="text" name="settings[mail_smtp_host]" value="<?= htmlspecialchars($allSettings['mail_smtp_host'] ?? 'smtp.gmail.com') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label><input type="number" name="settings[mail_smtp_port]" value="<?= htmlspecialchars($allSettings['mail_smtp_port'] ?? '587') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Güvenlik</label>
                                <select name="settings[mail_smtp_secure]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <option value="tls" <?= ($allSettings['mail_smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= ($allSettings['mail_smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="" <?= ($allSettings['mail_smtp_secure'] ?? '') === '' ? 'selected' : '' ?>>Yok</option>
                                </select>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">SMTP Kullanıcı Adı</label><input type="text" name="settings[mail_smtp_username]" value="<?= htmlspecialchars($allSettings['mail_smtp_username'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">SMTP Şifresi</label><input type="password" name="settings[mail_smtp_password]" value="<?= htmlspecialchars($allSettings['mail_smtp_password'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Gönderici E-posta</label><input type="email" name="settings[mail_from_email]" value="<?= htmlspecialchars($allSettings['mail_from_email'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Gönderici Adı</label><input type="text" name="settings[mail_from_name]" value="<?= htmlspecialchars($allSettings['mail_from_name'] ?? 'Randevu Sistemi') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                        </div>
                    </div>

                    <!-- ─── TAB: SMS ─── -->
                    <div class="settings-panel" data-tab="sms" style="display:<?= $activeSettingsTab === 'sms' ? 'block' : 'none' ?>">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">SMS Sağlayıcı</label>
                                <select name="settings[sms_provider]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <option value="log" <?= ($allSettings['sms_provider'] ?? '') === 'log' ? 'selected' : '' ?>>Log (Test)</option>
                                    <option value="twilio" <?= ($allSettings['sms_provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                                    <option value="netgsm" <?= ($allSettings['sms_provider'] ?? '') === 'netgsm' ? 'selected' : '' ?>>NetGSM</option>
                                </select>
                            </div>
                            <div class="md:col-span-2"><hr class="border-gray-200"><p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mt-3 mb-2">Twilio Ayarları</p></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Account SID</label><input type="text" name="settings[sms_twilio_sid]" value="<?= htmlspecialchars($allSettings['sms_twilio_sid'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Auth Token</label><input type="password" name="settings[sms_twilio_token]" value="<?= htmlspecialchars($allSettings['sms_twilio_token'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Gönderici Numara</label><input type="text" name="settings[sms_twilio_from]" value="<?= htmlspecialchars($allSettings['sms_twilio_from'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div class="md:col-span-2"><hr class="border-gray-200"><p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mt-3 mb-2">NetGSM Ayarları</p></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Kullanıcı Kodu</label><input type="text" name="settings[sms_netgsm_user]" value="<?= htmlspecialchars($allSettings['sms_netgsm_user'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Şifre</label><input type="password" name="settings[sms_netgsm_pass]" value="<?= htmlspecialchars($allSettings['sms_netgsm_pass'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Mesaj Başlığı</label><input type="text" name="settings[sms_netgsm_msgheader]" value="<?= htmlspecialchars($allSettings['sms_netgsm_msgheader'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                        </div>
                    </div>

                    <!-- ─── TAB: ÖDEME ─── -->
                    <div class="settings-panel" data-tab="payment" style="display:<?= $activeSettingsTab === 'payment' ? 'block' : 'none' ?>">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Ödeme Sağlayıcı</label>
                                <select name="settings[payment_provider]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <option value="iyzico" <?= ($allSettings['payment_provider'] ?? '') === 'iyzico' ? 'selected' : '' ?>>Iyzico</option>
                                </select>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Mod</label>
                                <select name="settings[payment_iyzico_sandbox]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <option value="1" <?= ($allSettings['payment_iyzico_sandbox'] ?? '1') === '1' ? 'selected' : '' ?>>Sandbox (Test)</option>
                                    <option value="0" <?= ($allSettings['payment_iyzico_sandbox'] ?? '1') === '0' ? 'selected' : '' ?>>Canlı</option>
                                </select>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">API Key</label><input type="text" name="settings[payment_iyzico_api_key]" value="<?= htmlspecialchars($allSettings['payment_iyzico_api_key'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label><input type="password" name="settings[payment_iyzico_secret_key]" value="<?= htmlspecialchars($allSettings['payment_iyzico_secret_key'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">API Base URL</label><input type="text" name="settings[payment_iyzico_base_url]" value="<?= htmlspecialchars($allSettings['payment_iyzico_base_url'] ?? 'https://sandbox-api.iyzipay.com') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                        </div>
                    </div>

                    <!-- ─── TAB: DİL & PWA ─── -->
                    <div class="settings-panel" data-tab="lang" style="display:<?= $activeSettingsTab === 'lang' ? 'block' : 'none' ?>">
                        <div class="p-6">
                            <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Dil Ayarları</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                                    <label class="text-sm font-semibold text-gray-700 mb-3 block">Varsayılan Dil</label>
                                    <div class="flex gap-2">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="settings[app_language]" value="tr" class="sr-only peer" <?= ($allSettings['app_language'] ?? 'tr') === 'tr' ? 'checked' : '' ?>>
                                            <div class="flex items-center gap-2 px-3 py-2.5 border-2 rounded-lg peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 transition-all">
                                                <span class="w-7 h-7 rounded-full bg-red-600 flex items-center justify-center text-white text-xs font-bold shrink-0">TR</span>
                                                <span class="text-sm font-medium text-gray-900">Türkçe</span>
                                            </div>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="settings[app_language]" value="en" class="sr-only peer" <?= ($allSettings['app_language'] ?? 'tr') === 'en' ? 'checked' : '' ?>>
                                            <div class="flex items-center gap-2 px-3 py-2.5 border-2 rounded-lg peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 transition-all">
                                                <span class="w-7 h-7 rounded-full bg-blue-700 flex items-center justify-center text-white text-xs font-bold shrink-0">EN</span>
                                                <span class="text-sm font-medium text-gray-900">English</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                                    <label class="text-sm font-semibold text-gray-700 mb-3 block">Otomatik Dil Algılama</label>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Tarayıcı diline göre yönlendir</span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[app_language_auto]" value="0">
                                            <input type="checkbox" name="settings[app_language_auto]" value="1" class="sr-only peer" <?= ($allSettings['app_language_auto'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <hr class="border-gray-200 my-5">
                            <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg> Mobil Uygulama (PWA)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kısa Ad</label><input type="text" name="settings[app_short_name]" value="<?= htmlspecialchars($allSettings['app_short_name'] ?? 'Randevu') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tema Rengi</label><div class="flex items-center gap-2"><input type="color" name="settings[app_theme_color]" value="<?= htmlspecialchars($allSettings['app_theme_color'] ?? '#4F46E5') ?>" class="w-10 h-9 rounded border border-gray-300 cursor-pointer"><span class="text-xs text-gray-400"><?= htmlspecialchars($allSettings['app_theme_color'] ?? '#4F46E5') ?></span></div></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Arka Plan Rengi</label><div class="flex items-center gap-2"><input type="color" name="settings[app_bg_color]" value="<?= htmlspecialchars($allSettings['app_bg_color'] ?? '#F9FAFB') ?>" class="w-10 h-9 rounded border border-gray-300 cursor-pointer"><span class="text-xs text-gray-400"><?= htmlspecialchars($allSettings['app_bg_color'] ?? '#F9FAFB') ?></span></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TAB: API ─── -->
                    <div class="settings-panel" data-tab="api" style="display:<?= $activeSettingsTab === 'api' ? 'block' : 'none' ?>">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                                    <label class="text-sm font-semibold text-gray-700 mb-3 block">API Erişimi</label>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Üçüncü parti uygulamaların bağlanmasına izin ver</span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[api_enabled]" value="0">
                                            <input type="checkbox" name="settings[api_enabled]" value="1" class="sr-only peer" <?= ($allSettings['api_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                        </label>
                                    </div>
                                    <div class="mt-2 flex items-center gap-1.5 text-xs <?= ($allSettings['api_enabled'] ?? '1') === '1' ? 'text-green-600' : 'text-gray-400' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= ($allSettings['api_enabled'] ?? '1') === '1' ? 'bg-green-500' : 'bg-gray-300' ?>"></span>
                                        <?= ($allSettings['api_enabled'] ?? '1') === '1' ? 'API aktif' : 'API kapalı' ?>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                                    <label class="text-sm font-semibold text-gray-700 mb-3 block">API Anahtarı</label>
                                    <div class="relative">
                                        <input type="text" id="apiKeyDisplay" value="<?= htmlspecialchars($allSettings['api_key'] ?? '') ?>" readonly class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg text-xs bg-white font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="Anahtar oluşturulmamış">
                                        <button type="button" onclick="copyApiKey()" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded hover:bg-gray-200 text-gray-400" title="Kopyala">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    <div class="mt-3 flex gap-2">
                                        <button type="submit" form="formApiKey" onclick="return confirm('Yeni anahtar oluşturulursa eski anahtar geçersiz olur. Devam edilsin mi?')" class="px-3 py-1.5 bg-amber-500 text-white rounded-lg text-xs font-medium hover:bg-amber-600 transition-all">Yeni Anahtar Oluştur</button>
                                    </div>
                                    <?php if (isset($_SESSION['new_api_key'])): ?>
                                        <div class="mt-2 flex items-center gap-1.5 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span><strong>Oluşturuldu:</strong> <code class="bg-green-100 px-1 rounded font-mono"><?= htmlspecialchars($_SESSION['new_api_key']) ?></code></span>
                                        </div>
                                        <?php unset($_SESSION['new_api_key']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TAB: YEDEKLEME ─── -->
                    <div class="settings-panel" data-tab="backup" style="display:<?= $activeSettingsTab === 'backup' ? 'block' : 'none' ?>">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Otomatik Yedekleme</label>
                                    <select name="settings[backup_auto_enabled]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                        <option value="1" <?= ($allSettings['backup_auto_enabled'] ?? '0') === '1' ? 'selected' : '' ?>><?= _t('active') ?></option>
                                        <option value="0" <?= ($allSettings['backup_auto_enabled'] ?? '0') === '0' ? 'selected' : '' ?>><?= _t('passive') ?></option>
                                    </select>
                                </div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1"><?= _t('backup_frequency') ?></label>
                                    <select name="settings[backup_frequency]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                        <option value="daily" <?= ($allSettings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>><?= _t('every_day') ?></option>
                                        <option value="weekly" <?= ($allSettings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>><?= _t('every_week') ?></option>
                                        <option value="monthly" <?= ($allSettings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>><?= _t('every_month') ?></option>
                                    </select>
                                </div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1"><?= _t('backup_retention') ?></label><input type="number" name="settings[backup_retention]" value="<?= (int)($allSettings['backup_retention'] ?? 7) ?>" min="1" max="365" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            </div>
                            <div class="flex gap-2 pt-3 border-t border-gray-200">
                                <button type="submit" form="formBackup" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-all shadow-sm"><?= _t('backup_now') ?></button>
                                <?php $backupDir = __DIR__ . '/../backups/';
                                if (is_dir($backupDir)): $bFiles = scandir($backupDir);
                                    $bFiles = array_filter(is_array($bFiles) ? $bFiles : [], fn($f) => str_ends_with($f, '.sql.gz'));
                                    if (!empty($bFiles)): ?>
                                    <select onchange="if(this.value) window.open('?tab=settings&download='+encodeURIComponent(this.value),'_blank')" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="">Yedek İndir...</option>
                                        <?php foreach ($bFiles as $bf): ?><option value="<?= basename($bf) ?>"><?= basename($bf) ?></option><?php endforeach; ?>
                                    </select>
                                <?php endif; endif; ?>
                                <?php if ($allSettings['last_backup']): ?>
                                    <span class="text-xs text-gray-400 self-center ml-2">Son: <?= $allSettings['last_backup'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TAB: HATA ─── -->
                    <div class="settings-panel" data-tab="error" style="display:<?= $activeSettingsTab === 'error' ? 'block' : 'none' ?>">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Sentry DSN</label><input type="text" name="settings[sentry_dsn]" value="<?= htmlspecialchars($allSettings['sentry_dsn'] ?? '') ?>" placeholder="https://..." class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"><p class="text-xs text-gray-400 mt-1">Boş bırakılırsa Sentry devre dışıdır.</p></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Hata Kaydı Seviyesi</label>
                                    <select name="settings[error_log_level]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                        <option value="0" <?= ($allSettings['error_log_level'] ?? '2') === '0' ? 'selected' : '' ?>>Kapalı</option>
                                        <option value="1" <?= ($allSettings['error_log_level'] ?? '2') === '1' ? 'selected' : '' ?>>Sadece Fatal</option>
                                        <option value="2" <?= ($allSettings['error_log_level'] ?? '2') === '2' ? 'selected' : '' ?>>Tümü</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2 pt-3 border-t border-gray-200">
                                <button type="submit" form="formErrorView" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-all">Hata Kaydını Görüntüle</button>
                                <button type="submit" form="formErrorTest" class="px-3 py-2 bg-purple-100 text-purple-700 rounded-lg text-sm hover:bg-purple-200 transition-all">Sentry Test</button>
                                <button type="submit" form="formErrorClear" class="px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm hover:bg-red-200 transition-all">Temizle</button>
                            </div>
                        </div>
                    </div>

                    <!-- ─── TAB: ÖNBELLEK ─── -->
                    <div class="settings-panel" data-tab="cache" style="display:<?= $activeSettingsTab === 'cache' ? 'block' : 'none' ?>">
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1"><?= _t('cache_type') ?></label>
                                    <select name="settings[cache_type]" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                        <option value="file" <?= ($allSettings['cache_type'] ?? 'file') === 'file' ? 'selected' : '' ?>>File Cache</option>
                                        <?php if (class_exists('\Redis')): ?><option value="redis" <?= ($allSettings['cache_type'] ?? '') === 'redis' ? 'selected' : '' ?>>Redis</option><?php endif; ?>
                                        <?php if (class_exists('\Memcached')): ?><option value="memcache" <?= ($allSettings['cache_type'] ?? '') === 'memcache' ? 'selected' : '' ?>>Memcache</option><?php endif; ?>
                                    </select>
                                </div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1"><?= _t('cache_server') ?></label><input type="text" name="settings[cache_server]" value="<?= htmlspecialchars($allSettings['cache_server'] ?? '127.0.0.1') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1"><?= _t('cache_port') ?></label><input type="number" name="settings[cache_port]" value="<?= htmlspecialchars($allSettings['cache_port'] ?? '6379') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></div>
                            </div>
                            <div class="flex gap-2 pt-3 border-t border-gray-200">
                                <button type="submit" form="formFlushCache" class="px-3 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm hover:bg-orange-200 transition-all">Önbelleği Temizle</button>
                            </div>
                        </div>
                    </div>

                    <!-- ─── STICKY SAVE ─── -->
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex items-center justify-between shadow-lg">
                        <p class="text-xs text-gray-400">Tüm ayarlar veritabanında güvenle saklanır.</p>
                        <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg">Ayarları Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
