<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../lib/Settings.php';
Settings::init($pdo);
Security::init($pdo);
Security::verifyAdminSession();
$apiEnabled = Settings::get('api_enabled', '1');
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Test Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
pre { white-space: pre-wrap; word-break: break-word; }
.method-badge { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px; }
.method-GET { background: #dbeafe; color: #1d4ed8; }
.method-POST { background: #d1fae5; color: #059669; }
.method-PUT { background: #fef3c7; color: #d97706; }
.method-DELETE { background: #fce7f3; color: #db2777; }
.tab-btn.active { border-bottom-color: #4f46e5; color: #4f46e5; }
</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
<div class="max-w-6xl mx-auto p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">API Test Panel</h1>
            <p class="text-sm text-gray-500 mt-1">Tüm API endpoint'lerini test et</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $apiEnabled === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">API: <?= $apiEnabled === '1' ? 'Aktif' : 'Kapalı' ?></span>
            <a href="../admin/index.php?tab=settings&st=api" class="text-xs text-indigo-600 hover:underline">Ayarlar</a>
        </div>
    </div>

    <?php if ($apiEnabled !== '1'): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 mb-6">
        API kapalı. Ayarlar sayfasından aktifleştirin: <a href="../admin/index.php?tab=settings&st=api" class="font-medium underline">Admin &rarr; Ayarlar &rarr; API</a>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Base URL</label>
            <div class="flex items-center gap-2 mt-1">
                <input id="baseUrl" type="text" value="<?= htmlspecialchars($baseUrl) ?>" readonly class="flex-1 text-xs font-mono bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-600">
                <button onclick="copyText('baseUrl')" class="px-3 py-2 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg">Kopyala</button>
            </div>
        </div>
    </div>
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">API Anahtarı</label>
            <div class="flex items-center gap-2 mt-1">
                <input id="apiKey" type="password" class="flex-1 text-xs font-mono bg-gray-50 border border-gray-200 rounded-lg px-3 py-2" placeholder="API anahtarını yapıştırın...">
                <button onclick="toggleKeyVisibility()" class="px-3 py-2 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg">Göster</button>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Anahtarı <a href="../admin/index.php?tab=settings&st=api" class="text-indigo-600 underline">Admin → Ayarlar → API</a> sayfasından kopyalayın</p>
        </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sticky top-4">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 px-2">Endpoint'ler</div>
                <div id="endpointList" class="space-y-0.5"></div>
            </div>
        </div>
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div id="methodBadge" class="method-badge method-GET">GET</div>
                    <code id="endpointPath" class="text-sm font-mono text-gray-700">/</code>
                </div>

                <div id="paramSection" class="space-y-3 mb-4">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Parametreler</div>
                    <div id="paramFields"></div>
                </div>

                <div class="mb-4">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">İstek Gövdesi (JSON)</div>
                    <textarea id="requestBody" rows="5" class="w-full font-mono text-xs border border-gray-200 rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder='{"key": "value"}'></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button id="sendBtn" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-sm">Gönder</button>
                    <span id="statusBadge" class="text-xs font-mono px-2 py-1 rounded hidden"></span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Yanıt</h3>
                    <div class="flex gap-2">
                        <button onclick="copyResponse()" class="text-xs text-gray-400 hover:text-gray-600">Kopyala</button>
                        <button onclick="clearResponse()" class="text-xs text-gray-400 hover:text-gray-600">Temizle</button>
                    </div>
                </div>
                <pre id="responseOutput" class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-auto max-h-96 leading-relaxed">Henüz istek yapılmadı.</pre>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = document.getElementById('baseUrl').value;

const endpoints = [
    { path: '/', method: 'GET', params: [], desc: 'API Info', body: false, group: 'Keşif' },
    { path: '/services', method: 'GET', params: [{key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}], desc: 'Hizmet Listesi', body: false, group: 'Servisler' },
    { path: '/services/:id', method: 'GET', params: [
        {key:':id',label:'ID',ph:'1'}
    ], desc: 'Hizmet Detay', body: false, group: 'Servisler' },
    { path: '/services', method: 'POST', params: [], desc: 'Hizmet Ekle', body: true, group: 'Servisler',
      bodyTemplate: '{\n  "name": "Yeni Hizmet",\n  "duration": 30,\n  "price": 100.00,\n  "description": "Açıklama",\n  "requires_payment": 0,\n  "is_active": 1\n}' },
    { path: '/services/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Hizmet Güncelle', body: true, group: 'Servisler',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "price": 150.00\n}' },
    { path: '/services/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Hizmet Sil', body: false, group: 'Servisler' },
    { path: '/appointments', method: 'GET', params: [
        {key:'date',label:'Tarih',ph:'2026-06-18'},
        {key:'status',label:'Durum',ph:'pending/confirmed/completed/cancelled'},
        {key:'customer_id',label:'Müşteri ID'},
        {key:'employee_id',label:'Çalışan ID'},
        {key:'from',label:'Başlangıç',ph:'2026-06-01'},
        {key:'to',label:'Bitiş',ph:'2026-06-30'},
        {key:'page',label:'Sayfa'},
        {key:'per_page',label:'Sayfa Başına'}
    ], desc: 'Randevu Listesi', body: false, group: 'Randevular' },
    { path: '/appointments/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu Detay', body: false, group: 'Randevular' },
    { path: '/appointments', method: 'POST', params: [], desc: 'Randevu Oluştur', body: true, group: 'Randevular',
      bodyTemplate: '{\n  "service_id": 1,\n  "employee_id": 1,\n  "customer_name": "Test Müşteri",\n  "customer_email": "test@example.com",\n  "customer_phone": "05331112233",\n  "appointment_date": "2026-06-20",\n  "appointment_time": "10:00",\n  "notes": "Test randevusu"\n}' },
    { path: '/appointments/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu Güncelle', body: true, group: 'Randevular',
      bodyTemplate: '{\n  "status": "confirmed",\n  "notes": "Onaylandı"\n}' },
    { path: '/appointments/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu İptal Et', body: false, group: 'Randevular' },
    { path: '/employees', method: 'GET', params: [
        {key:'include_hours',label:'Saatler Dahil',ph:'1'},
        {key:'page',label:'Sayfa'},
        {key:'per_page',label:'Sayfa Başına'}
    ], desc: 'Çalışan Listesi', body: false, group: 'Çalışanlar' },
    { path: '/employees/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Detay', body: false, group: 'Çalışanlar' },
    { path: '/employees', method: 'POST', params: [], desc: 'Çalışan Ekle', body: true, group: 'Çalışanlar',
      bodyTemplate: '{\n  "name": "Yeni Çalışan",\n  "email": "calisan@example.com",\n  "phone": "05330001122",\n  "is_active": 1\n}' },
    { path: '/employees/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Güncelle', body: true, group: 'Çalışanlar',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "is_active": 1,\n  "hours": [{"day_of_week":1,"is_open":1,"open_time":"09:00","close_time":"18:00"}]\n}' },
    { path: '/employees/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Sil', body: false, group: 'Çalışanlar' },
    { path: '/customers', method: 'GET', params: [{key:'search',label:'Ara',ph:'ad veya tel'},{key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}], desc: 'Müşteri Listesi', body: false, group: 'Müşteriler' },
    { path: '/customers/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Detay (randevu geçmişi ile)', body: false, group: 'Müşteriler' },
    { path: '/customers', method: 'POST', params: [], desc: 'Müşteri Ekle', body: true, group: 'Müşteriler',
      bodyTemplate: '{\n  "name": "Ali Veli",\n  "email": "ali@example.com",\n  "phone": "05331112244",\n  "notes": ""\n}' },
    { path: '/customers/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Güncelle', body: true, group: 'Müşteriler',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "phone": "05331112255"\n}' },
    { path: '/customers/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Sil', body: false, group: 'Müşteriler' },
    { path: '/available-times', method: 'GET', params: [
        {key:'service_id',label:'Hizmet ID',ph:'1'},
        {key:'date',label:'Tarih',ph:'2026-06-20'},
        {key:'employee_id',label:'Çalışan ID',ph:'1'}
    ], desc: 'Müsait Saatler', body: false, group: 'Zamanlama' },
    { path: '/working-hours', method: 'GET', params: [], desc: 'Çalışma Saatleri', body: false, group: 'Zamanlama' },
    { path: '/working-hours/:id', method: 'GET', params: [{key:':id',label:'Gün (0-6)',ph:'1'}], desc: 'Günlük Çalışma Saati', body: false, group: 'Zamanlama' },
    { path: '/breaks', method: 'GET', params: [{key:'employee_id',label:'Çalışan ID'},{key:'day_of_week',label:'Gün (0-6)'}], desc: 'Mola Listesi', body: false, group: 'Zamanlama' },
    { path: '/breaks', method: 'POST', params: [], desc: 'Mola Ekle', body: true, group: 'Zamanlama',
      bodyTemplate: '{\n  "day_of_week": 1,\n  "start_time": "12:00",\n  "end_time": "13:00",\n  "label": "Öğle Molası",\n  "employee_id": null\n}' },
    { path: '/breaks/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Mola Sil', body: false, group: 'Zamanlama' },
    { path: '/series', method: 'GET', params: [], desc: 'Tekrarlanan Listesi', body: false, group: 'Tekrarlanan' },
    { path: '/series', method: 'POST', params: [], desc: 'Tekrarlanan Oluştur', body: true, group: 'Tekrarlanan',
      bodyTemplate: '{\n  "service_id": 1,\n  "employee_id": 1,\n  "customer_name": "Haftalık Müşteri",\n  "customer_phone": "05331112266",\n  "appointment_time": "14:00",\n  "frequency": "weekly",\n  "day_of_week": 2,\n  "start_date": "2026-06-22",\n  "end_date": "2026-09-22"\n}' },
    { path: '/series/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Tekrarlanan Sil', body: false, group: 'Tekrarlanan' },
    { path: '/bulk-message', method: 'POST', params: [], desc: 'Toplu Mesaj', body: true, group: 'Mesaj',
      bodyTemplate: '{\n  "type": "email",\n  "subject": "Kampanya",\n  "message": "Özel indirimlerimiz var!",\n  "audience": "all"\n}' },
    { path: '/dashboard/stats', method: 'GET', params: [], desc: 'Panel İstatistikleri', body: false, group: 'Panel' },
    { path: '/dashboard/charts', method: 'GET', params: [], desc: 'Panel Grafikleri', body: false, group: 'Panel' },
];

let currentEndpoint = null;

function renderEndpoints() {
    const container = document.getElementById('endpointList');
    const groups = {};
    endpoints.forEach(ep => {
        if (!groups[ep.group]) groups[ep.group] = [];
        groups[ep.group].push(ep);
    });

    let html = '';
    Object.keys(groups).forEach(group => {
        html += `<div class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-2 pt-3 pb-1">${group}</div>`;
        groups[group].forEach(ep => {
            const label = ep.path + ' ' + ep.desc;
            html += `<button onclick="selectEndpoint(${endpoints.indexOf(ep)})" class="w-full text-left px-2 py-1.5 text-xs rounded-lg hover:bg-indigo-50 transition-colors endpoint-btn" data-index="${endpoints.indexOf(ep)}">
                <span class="method-badge method-${ep.method}">${ep.method}</span>
                <span class="text-gray-600 ml-1">${ep.desc}</span>
            </button>`;
        });
    });
    container.innerHTML = html;
}

function selectEndpoint(index) {
    document.querySelectorAll('.endpoint-btn').forEach(b => b.classList.remove('bg-indigo-50', 'font-medium'));
    const btn = document.querySelector(`.endpoint-btn[data-index="${index}"]`);
    if (btn) btn.classList.add('bg-indigo-50', 'font-medium');

    const ep = endpoints[index];
    currentEndpoint = ep;

    document.getElementById('methodBadge').className = `method-badge method-${ep.method}`;
    document.getElementById('methodBadge').textContent = ep.method;
    document.getElementById('endpointPath').textContent = ep.path;

    // Params
    const paramSection = document.getElementById('paramFields');
    if (ep.params.length) {
        let html = '';
        ep.params.forEach(p => {
            const isPathParam = p.key.startsWith(':');
            html += `<div class="flex items-center gap-2">
                <label class="text-xs font-medium text-gray-600 w-24 shrink-0">${p.label}</label>
                <input type="${p.key === 'date' || p.key === 'from' || p.key === 'to' ? 'date' : 'text'}"
                       data-param="${p.key}"
                       placeholder="${p.ph || ''}"
                       class="flex-1 text-xs font-mono border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
            </div>`;
        });
        paramSection.innerHTML = html;
        paramSection.closest('#paramSection').style.display = '';
    } else {
        paramSection.closest('#paramSection').style.display = 'none';
    }

    // Body
    const bodyArea = document.getElementById('requestBody');
    if (ep.body) {
        bodyArea.style.display = '';
        if (ep.bodyTemplate) {
            bodyArea.value = ep.bodyTemplate;
        } else {
            bodyArea.value = '{\n  \n}';
        }
    } else {
        bodyArea.style.display = 'none';
        bodyArea.value = '';
    }

    document.getElementById('responseOutput').textContent = 'Henüz istek yapılmadı.';
    document.getElementById('statusBadge').classList.add('hidden');
}

document.getElementById('sendBtn').addEventListener('click', function() {
    if (!currentEndpoint) return;
    const ep = currentEndpoint;

    let path = ep.path;
    let queryParams = [];
    document.querySelectorAll('#paramFields input').forEach(input => {
        const key = input.dataset.param;
        const val = input.value.trim();
        if (!val) return;
        if (key.startsWith(':')) {
            path = path.replace(key, encodeURIComponent(val));
        } else {
            queryParams.push(key + '=' + encodeURIComponent(val));
        }
    });

    let queryStr = queryParams.length ? '?' + queryParams.join('&') : '';
    if (ep.path === '/' || ep.path === '/api') queryStr = '';
    const url = BASE_URL.replace(/\/+$/, '') + path + queryStr;

    const apiKey = document.getElementById('apiKey').value.trim();
    if (!apiKey) {
        document.getElementById('responseOutput').textContent = 'HATA: Lütfen API anahtarını girin';
        return;
    }
    const options = {
        method: ep.method,
        headers: {
            'Authorization': 'Bearer ' + apiKey,
            'Content-Type': 'application/json'
        }
    };

    if (ep.body) {
        try {
            const bodyVal = document.getElementById('requestBody').value.trim();
            if (bodyVal) {
                JSON.parse(bodyVal);
                options.body = bodyVal;
            }
        } catch (e) {
            document.getElementById('responseOutput').textContent = 'HATA: Geçersiz JSON formatı';
            return;
        }
    }

    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = 'İstek yapılıyor...';
    document.getElementById('responseOutput').textContent = 'İstek yapılıyor...';

    fetch(url, options)
        .then(async res => {
            const status = document.getElementById('statusBadge');
            status.className = `px-2 py-1 rounded text-xs font-mono ${res.ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            status.textContent = res.status + ' ' + res.statusText;
            status.classList.remove('hidden');

            let respText = '';
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const json = await res.json();
                respText = JSON.stringify(json, null, 2);
            } else {
                respText = await res.text();
            }

            const output = `> ${ep.method} ${url}\n\n${respText}`;
            document.getElementById('responseOutput').textContent = output;
        })
        .catch(err => {
            document.getElementById('responseOutput').textContent = 'HATA: ' + err.message;
            const status = document.getElementById('statusBadge');
            status.className = 'px-2 py-1 rounded text-xs font-mono bg-red-100 text-red-700';
            status.textContent = 'Hata';
            status.classList.remove('hidden');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Gönder';
        });
});

function copyText(id) {
    const el = document.getElementById(id);
    el.select();
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = el.parentElement.querySelector('button');
        const orig = btn.textContent;
        btn.textContent = 'Kopyalandı!';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}

function copyResponse() {
    const text = document.getElementById('responseOutput').textContent;
    navigator.clipboard.writeText(text);
}

function clearResponse() {
    document.getElementById('responseOutput').textContent = 'Henüz istek yapılmadı.';
    document.getElementById('statusBadge').classList.add('hidden');
}

function toggleKeyVisibility() {
    const input = document.getElementById('apiKey');
    const btn = input.parentElement.querySelector('button');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Gizle';
    } else {
        input.type = 'password';
        btn.textContent = 'Göster';
    }
}
renderEndpoints();
selectEndpoint(0);
</script>
</body>
</html>
