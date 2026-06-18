    <!-- Main Content Wrapper Close -->
            </div>
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }
    function showToast(msg, type) {
        var t = document.createElement('div');
        t.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all duration-300 ' + (type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500 text-yellow-900' : 'bg-green-500');
        t.textContent = msg;
        t.style.transform = 'translateX(120%)';
        document.body.appendChild(t);
        requestAnimationFrame(function(){ t.style.transform = 'translateX(0)'; });
        setTimeout(function(){ t.style.transform = 'translateX(120%)'; setTimeout(function(){ t.remove(); }, 300); }, 3500);
    }
    function confirmDelete(msg) {
        return confirm(msg || '<?= _t('confirm_delete') ?>');
    }
    function copyApiKey() {
        const input = document.getElementById('apiKeyDisplay');
        if (!input || !input.value) return;
        navigator.clipboard.writeText(input.value).then(function() {
            const btn = input.parentElement.querySelector('button');
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            }
        });
    }
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../pwa/service-worker.js');
    }
    </script>

    <?php if (isset($settingsPage)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('subTabSelect');
        if (sel) { sel.addEventListener('change', function(){ window.location.href='?tab=settings&st='+this.value; }); }
    });
    </script>
    <?php endif; ?>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.x/dist/cdn.min.js"></script>
</body>
</html>
