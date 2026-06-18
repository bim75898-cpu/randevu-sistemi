<?php
$apiBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/api/';
$apiKeySetting = Settings::get('api_key', '');
$apiEnabled = Settings::get('api_enabled', '1');
?>
<style>
.api-method { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px; }
.api-GET { background: #dbeafe; color: #1d4ed8; }
.api-POST { background: #d1fae5; color: #059669; }
.api-PUT { background: #fef3c7; color: #d97706; }
.api-DELETE { background: #fce7f3; color: #db2777; }
.code-tab { padding: 6px 14px; font-size: 12px; font-weight: 500; border-radius: 6px 6px 0 0; cursor: pointer; border: 1px solid transparent; border-bottom: none; transition: all 0.15s; }
.code-tab.active { background: #1e293b; color: #e2e8f0; border-color: #334155; }
.code-tab:not(.active) { background: #f1f5f9; color: #64748b; }
.code-tab:not(.active):hover { background: #e2e8f0; }
.copy-btn { position: absolute; top: 8px; right: 8px; padding: 4px 10px; font-size: 11px; font-weight: 500; border-radius: 4px; background: #334155; color: #94a3b8; border: none; cursor: pointer; transition: all 0.15s; }
.copy-btn:hover { background: #475569; color: #e2e8f0; }
</style>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">API Test Console</h1>
        <p class="text-sm text-gray-500 mt-1">REST API uç noktalarını test edin ve entegrasyon kodlarını görün</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $apiEnabled === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">API: <?= $apiEnabled === '1' ? 'Aktif' : 'Kapalı' ?></span>
        <a href="settings.php?st=api" class="text-xs text-indigo-600 hover:underline">Ayarlar</a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
    <!-- Sol: Endpoint Listesi -->
    <div class="xl:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sticky top-4">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 px-2">Endpoint'ler</div>
            <div id="apiEndpointList" class="space-y-0.5 max-h-[70vh] overflow-y-auto"></div>
        </div>
    </div>

    <!-- Sağ: Test + Kod Örnekleri -->
    <div class="xl:col-span-4 space-y-5">
        <!-- Test Kısmı -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span id="apiMethodBadge" class="api-method api-GET">GET</span>
                    <code id="apiEndpointPath" class="text-sm font-mono text-gray-700">/</code>
                </div>
            </div>

            <div id="apiParamSection" class="space-y-3 mb-4">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Parametreler</div>
                <div id="apiParamFields"></div>
            </div>

            <div class="mb-4">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">İstek Gövdesi (JSON)</div>
                <textarea id="apiRequestBody" rows="5" class="w-full font-mono text-xs border border-gray-200 rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder='{"key": "value"}'></textarea>
            </div>

            <div class="flex items-center gap-3">
                <button id="apiSendBtn" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-all shadow-sm">Gönder</button>
                <span id="apiStatusBadge" class="text-xs font-mono px-2 py-1 rounded hidden"></span>
            </div>
        </div>

        <!-- Kod Örnekleri -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 pt-4 pb-0">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Kod Örnekleri</h3>
                <div class="flex gap-0.5 overflow-x-auto" id="codeTabs">
                    <button class="code-tab active" data-lang="curl">cURL</button>
                    <button class="code-tab" data-lang="php">PHP</button>
                    <button class="code-tab" data-lang="javascript">JavaScript</button>
                    <button class="code-tab" data-lang="python">Python</button>
                </div>
            </div>
            <div class="relative">
                <button class="copy-btn" onclick="copyCodeExample()">Kopyala</button>
                <pre id="codeExampleOutput" class="bg-slate-900 text-slate-100 p-5 text-xs font-mono overflow-auto leading-relaxed max-h-64">Bir endpoint seçin...</pre>
            </div>
        </div>

        <!-- Yanıt -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Yanıt</h3>
                <div class="flex gap-2">
                    <button onclick="apiCopyResponse()" class="text-xs text-gray-400 hover:text-gray-600">Kopyala</button>
                    <button onclick="apiClearResponse()" class="text-xs text-gray-400 hover:text-gray-600">Temizle</button>
                </div>
            </div>
            <pre id="apiResponseOutput" class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-auto max-h-72 leading-relaxed">Henüz istek yapılmadı.</pre>
        </div>
    </div>
</div>

<script>
const API_BASE_URL = <?= json_encode($apiBaseUrl) ?>;
const API_KEY = <?= json_encode($apiKeySetting) ?>;

const apiEndpoints = [
    { path: '/', method: 'GET', params: [], desc: 'API Info', body: false, group: 'Keşif' },
    { path: '/services', method: 'GET', params: [{key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}], desc: 'Hizmet Listesi', body: false, group: 'Servisler' },
    { path: '/services/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Hizmet Detay', body: false, group: 'Servisler' },
    { path: '/services', method: 'POST', params: [], desc: 'Hizmet Ekle', body: true, group: 'Servisler',
      bodyTemplate: '{\n  "name": "Yeni Hizmet",\n  "duration": 30,\n  "price": 100.00,\n  "description": "Açıklama",\n  "requires_payment": 0,\n  "is_active": 1\n}' },
    { path: '/services/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Hizmet Güncelle', body: true, group: 'Servisler',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "price": 150.00\n}' },
    { path: '/services/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Hizmet Sil', body: false, group: 'Servisler' },
    { path: '/appointments', method: 'GET', params: [
        {key:'date',label:'Tarih',ph:'2026-06-18'},{key:'status',label:'Durum',ph:'pending/confirmed/completed/cancelled'},
        {key:'customer_id',label:'Müşteri ID'},{key:'employee_id',label:'Çalışan ID'},
        {key:'from',label:'Başlangıç',ph:'2026-06-01'},{key:'to',label:'Bitiş',ph:'2026-06-30'},
        {key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}
    ], desc: 'Randevu Listesi', body: false, group: 'Randevular' },
    { path: '/appointments/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu Detay', body: false, group: 'Randevular' },
    { path: '/appointments', method: 'POST', params: [], desc: 'Randevu Oluştur', body: true, group: 'Randevular',
      bodyTemplate: '{\n  "service_id": 1,\n  "employee_id": 1,\n  "customer_name": "Test Müşteri",\n  "customer_email": "test@example.com",\n  "customer_phone": "05331112233",\n  "appointment_date": "2026-06-20",\n  "appointment_time": "10:00",\n  "notes": "Test randevusu"\n}' },
    { path: '/appointments/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu Güncelle', body: true, group: 'Randevular',
      bodyTemplate: '{\n  "status": "confirmed",\n  "notes": "Onaylandı"\n}' },
    { path: '/appointments/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Randevu İptal Et', body: false, group: 'Randevular' },
    { path: '/employees', method: 'GET', params: [{key:'include_hours',label:'Saatler Dahil',ph:'1'},{key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}], desc: 'Çalışan Listesi', body: false, group: 'Çalışanlar' },
    { path: '/employees/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Detay', body: false, group: 'Çalışanlar' },
    { path: '/employees', method: 'POST', params: [], desc: 'Çalışan Ekle', body: true, group: 'Çalışanlar',
      bodyTemplate: '{\n  "name": "Yeni Çalışan",\n  "email": "calisan@example.com",\n  "phone": "05330001122",\n  "is_active": 1\n}' },
    { path: '/employees/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Güncelle', body: true, group: 'Çalışanlar',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "is_active": 1,\n  "hours": [{"day_of_week":1,"is_open":1,"open_time":"09:00","close_time":"18:00"}]\n}' },
    { path: '/employees/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Çalışan Sil', body: false, group: 'Çalışanlar' },
    { path: '/customers', method: 'GET', params: [{key:'search',label:'Ara',ph:'ad veya tel'},{key:'page',label:'Sayfa'},{key:'per_page',label:'Sayfa Başına'}], desc: 'Müşteri Listesi', body: false, group: 'Müşteriler' },
    { path: '/customers/:id', method: 'GET', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Detay', body: false, group: 'Müşteriler' },
    { path: '/customers', method: 'POST', params: [], desc: 'Müşteri Ekle', body: true, group: 'Müşteriler',
      bodyTemplate: '{\n  "name": "Ali Veli",\n  "email": "ali@example.com",\n  "phone": "05331112244",\n  "notes": ""\n}' },
    { path: '/customers/:id', method: 'PUT', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Güncelle', body: true, group: 'Müşteriler',
      bodyTemplate: '{\n  "name": "Güncellendi",\n  "phone": "05331112255"\n}' },
    { path: '/customers/:id', method: 'DELETE', params: [{key:':id',label:'ID',ph:'1'}], desc: 'Müşteri Sil', body: false, group: 'Müşteriler' },
    { path: '/available-times', method: 'GET', params: [{key:'service_id',label:'Hizmet ID',ph:'1'},{key:'date',label:'Tarih',ph:'2026-06-20'},{key:'employee_id',label:'Çalışan ID',ph:'1'}], desc: 'Müsait Saatler', body: false, group: 'Zamanlama' },
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

let apiCurrentEndpoint = null;
let currentCodeLang = 'curl';

function buildUrl(ep) {
    let path = ep.path;
    let queryParams = [];
    document.querySelectorAll('#apiParamFields input').forEach(input => {
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
    return API_BASE_URL.replace(/\/+$/, '') + path + queryStr;
}

function getRequestBody() {
    try {
        const val = document.getElementById('apiRequestBody').value.trim();
        return val ? JSON.parse(val) : null;
    } catch (e) {
        return null;
    }
}

function generateCodeExample(ep, lang) {
    if (!ep) return '/* Bir endpoint seçin */';
    const url = buildUrl(ep);
    const method = ep.method.toLowerCase();
    const methodUpper = ep.method;
    const hasBody = ep.body && document.getElementById('apiRequestBody').value.trim();
    let body = null;
    try { body = hasBody ? JSON.parse(document.getElementById('apiRequestBody').value.trim()) : null; } catch(e) {}

    const authHeader = API_KEY ? `Authorization: Bearer ${API_KEY}` : '';

    if (lang === 'curl') {
        let lines = [`curl -X ${methodUpper} \\`];
        if (authHeader) lines.push(`  -H "${authHeader}" \\`);
        lines.push(`  -H "Content-Type: application/json" \\`);
        if (body) {
            const escaped = JSON.stringify(JSON.stringify(body, null, 2));
            lines.push(`  -d '${JSON.stringify(body, null, 2).replace(/'/g, "'\\''")}' \\`);
        }
        lines.push(`  "${url}"`);
        return lines.join('\n');
    }

    if (lang === 'php') {
        let lines = ['$url = "' + url + '";'];
        lines.push('');
        lines.push('$ch = curl_init($url);');
        lines.push('curl_setopt_array($ch, [');
        lines.push('    CURLOPT_RETURNTRANSFER => true,');
        lines.push('    CURLOPT_CUSTOMREQUEST => "' + methodUpper + '",');
        if (authHeader) lines.push('    CURLOPT_HTTPHEADER => ["' + authHeader + '", "Content-Type: application/json"],');
        else lines.push('    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],');
        if (body) lines.push('    CURLOPT_POSTFIELDS => json_encode(' + JSON.stringify(body) + '),');
        lines.push(']);');
        lines.push('');
        lines.push('$response = curl_exec($ch);');
        lines.push('$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);');
        lines.push('curl_close($ch);');
        lines.push('');
        lines.push('echo "HTTP $httpCode\\n";');
        lines.push('echo $response;');
        return lines.join('\n');
    }

    if (lang === 'javascript') {
        let lines = ['const url = "' + url + '";'];
        lines.push('');
        lines.push('const options = {');
        lines.push('  method: "' + methodUpper + '",');
        lines.push('  headers: {');
        if (authHeader) lines.push('    "Authorization": "' + API_KEY + '",');
        lines.push('    "Content-Type": "application/json"');
        lines.push('  }' + (body ? ',' : ''));
        if (body) {
            lines.push('  body: JSON.stringify(' + JSON.stringify(body, null, 4) + ')');
        }
        lines.push('};');
        lines.push('');
        lines.push('fetch(url, options)');
        lines.push('  .then(res => res.json())');
        lines.push('  .then(data => console.log(data))');
        lines.push('  .catch(err => console.error(err));');
        return lines.join('\n');
    }

    if (lang === 'python') {
        let lines = ['import requests', 'import json', ''];
        lines.push('url = "' + url + '"');
        lines.push('');
        let headers = {};
        if (API_KEY) headers['Authorization'] = 'Bearer ' + API_KEY;
        headers['Content-Type'] = 'application/json';
        lines.push('headers = ' + JSON.stringify(headers, null, 4));
        if (body) {
            lines.push('body = ' + JSON.stringify(body, null, 4));
            lines.push('');
            lines.push('response = requests.request("' + methodUpper + '", url, headers=headers, json=body)');
        } else {
            lines.push('');
            lines.push('response = requests.request("' + methodUpper + '", url, headers=headers)');
        }
        lines.push('print(f"HTTP {response.status_code}")');
        lines.push('print(response.text)');
        return lines.join('\n');
    }

    return '';
}

function updateCodeExample() {
    const ep = apiCurrentEndpoint;
    const lang = currentCodeLang;
    const code = generateCodeExample(ep, lang);
    document.getElementById('codeExampleOutput').textContent = code;
}

function apiRenderEndpoints() {
    const container = document.getElementById('apiEndpointList');
    const groups = {};
    apiEndpoints.forEach(ep => {
        if (!groups[ep.group]) groups[ep.group] = [];
        groups[ep.group].push(ep);
    });
    let html = '';
    Object.keys(groups).forEach(group => {
        html += `<div class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-2 pt-3 pb-1">${group}</div>`;
        groups[group].forEach(ep => {
            const idx = apiEndpoints.indexOf(ep);
            html += `<button onclick="apiSelectEndpoint(${idx})" class="w-full text-left px-2 py-1.5 text-xs rounded-lg hover:bg-indigo-50 transition-colors api-ep-btn" data-index="${idx}">
                <span class="api-method api-${ep.method}">${ep.method}</span>
                <span class="text-gray-600 ml-1">${ep.desc}</span>
            </button>`;
        });
    });
    container.innerHTML = html;
}

function apiSelectEndpoint(index) {
    document.querySelectorAll('.api-ep-btn').forEach(b => b.classList.remove('bg-indigo-50', 'font-medium'));
    const btn = document.querySelector(`.api-ep-btn[data-index="${index}"]`);
    if (btn) btn.classList.add('bg-indigo-50', 'font-medium');

    const ep = apiEndpoints[index];
    apiCurrentEndpoint = ep;

    document.getElementById('apiMethodBadge').className = 'api-method api-' + ep.method;
    document.getElementById('apiMethodBadge').textContent = ep.method;
    document.getElementById('apiEndpointPath').textContent = ep.path;

    const paramFields = document.getElementById('apiParamFields');
    if (ep.params.length) {
        let ph = '';
        ep.params.forEach(p => {
            const isPath = p.key.startsWith(':');
            ph += `<div class="flex items-center gap-2">
                <label class="text-xs font-medium text-gray-600 w-24 shrink-0">${p.label}</label>
                <input type="${p.key === 'date' || p.key === 'from' || p.key === 'to' ? 'date' : 'text'}"
                       data-param="${p.key}"
                       placeholder="${p.ph || ''}"
                       class="flex-1 text-xs font-mono border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                       oninput="updateCodeExample()">
            </div>`;
        });
        paramFields.innerHTML = ph;
        document.getElementById('apiParamSection').style.display = '';
    } else {
        document.getElementById('apiParamSection').style.display = 'none';
    }

    const bodyArea = document.getElementById('apiRequestBody');
    if (ep.body) {
        bodyArea.style.display = '';
        bodyArea.value = ep.bodyTemplate || '{\n  \n}';
    } else {
        bodyArea.style.display = 'none';
        bodyArea.value = '';
    }

    document.getElementById('apiResponseOutput').textContent = 'Henüz istek yapılmadı.';
    document.getElementById('apiStatusBadge').classList.add('hidden');
    updateCodeExample();
}

document.getElementById('apiSendBtn').addEventListener('click', function() {
    if (!apiCurrentEndpoint) return;
    const ep = apiCurrentEndpoint;
    const url = buildUrl(ep);

    const options = {
        method: ep.method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (API_KEY) options.headers['Authorization'] = 'Bearer ' + API_KEY;

    if (ep.body) {
        try {
            const bodyVal = document.getElementById('apiRequestBody').value.trim();
            if (bodyVal) {
                JSON.parse(bodyVal);
                options.body = bodyVal;
            }
        } catch (e) {
            document.getElementById('apiResponseOutput').textContent = 'HATA: Geçersiz JSON formatı';
            return;
        }
    }

    const sendBtn = document.getElementById('apiSendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = 'İstek yapılıyor...';
    document.getElementById('apiResponseOutput').textContent = 'İstek yapılıyor...';

    fetch(url, options)
        .then(async res => {
            const status = document.getElementById('apiStatusBadge');
            status.className = 'text-xs font-mono px-2 py-1 rounded ' + (res.ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            status.textContent = res.status + ' ' + res.statusText;
            status.classList.remove('hidden');

            let respText = '';
            const ct = res.headers.get('content-type') || '';
            if (ct.includes('application/json')) {
                respText = JSON.stringify(await res.json(), null, 2);
            } else {
                respText = await res.text();
            }
            document.getElementById('apiResponseOutput').textContent = '> ' + ep.method + ' ' + url + '\n\n' + respText;
        })
        .catch(err => {
            document.getElementById('apiResponseOutput').textContent = 'HATA: ' + err.message;
            const status = document.getElementById('apiStatusBadge');
            status.className = 'text-xs font-mono px-2 py-1 rounded bg-red-100 text-red-700';
            status.textContent = 'Hata';
            status.classList.remove('hidden');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Gönder';
        });
});

document.getElementById('apiRequestBody').addEventListener('input', updateCodeExample);

document.querySelectorAll('#codeTabs .code-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('#codeTabs .code-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentCodeLang = this.dataset.lang;
        updateCodeExample();
    });
});

function copyCodeExample() {
    const text = document.getElementById('codeExampleOutput').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.copy-btn');
        const orig = btn.textContent;
        btn.textContent = 'Kopyalandı!';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}

function apiCopyResponse() {
    const text = document.getElementById('apiResponseOutput').textContent;
    navigator.clipboard.writeText(text);
}
function apiClearResponse() {
    document.getElementById('apiResponseOutput').textContent = 'Henüz istek yapılmadı.';
    document.getElementById('apiStatusBadge').classList.add('hidden');
}

apiRenderEndpoints();
apiSelectEndpoint(0);
</script>
