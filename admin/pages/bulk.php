        <!-- ─── TAB: TOPLU MESAJ ─── -->
        <?php
        $sendResult = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk'])) {
            $type = $_POST['message_type'] ?? 'email';
            $subject = Security::sanitizeInput(trim($_POST['subject'] ?? ''));
            $message = Security::sanitizeInput($_POST['message'] ?? '');
            $filterType = $_POST['filter_type'] ?? 'all';

            $sql = "SELECT name, email, phone FROM customers WHERE 1=1";
            if ($filterType === 'has_appointment') $sql .= " AND total_appointments > 0";
            elseif ($filterType === 'no_appointment') $sql .= " AND total_appointments = 0";
            $allRecipients = $pdo->query($sql)->fetchAll();

            $sent = 0; $failed = 0;
            foreach ($allRecipients as $rcpt) {
                if ($type === 'email' && $subject && $message && $rcpt['email']) {
                    $body = "<p>Sn. " . htmlspecialchars($rcpt['name']) . ",</p><p>" . nl2br(htmlspecialchars($message)) . "</p>";
                    if (MailService::send($rcpt['email'], $subject, $body)) $sent++; else $failed++;
                } elseif ($type === 'sms' && $message && $rcpt['phone']) {
                    $smsText = "Sn. {$rcpt['name']}, $message";
                    if (SmsService::send($rcpt['phone'], $smsText)) $sent++; else $failed++;
                }
            }
            $sendResult = "<div class='p-4 rounded-lg " . ($failed > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-green-50 text-green-700 border border-green-200') . " text-sm'>$sent mesaj gönderildi" . ($failed > 0 ? ", $failed başarısız." : ".") . " Toplam: " . count($allRecipients) . " alıcı.</div>";
        }
        ?>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Toplu Mesaj Gönderimi</h1>

        <?php if ($sendResult) echo $sendResult; ?>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <input type="hidden" name="send_bulk" value="1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mesaj Türü</label>
                    <select name="message_type" id="msgType" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="email">E-posta</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hedef Kitle</label>
                    <select name="filter_type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="all">Tüm Müşteriler</option>
                        <option value="has_appointment">Randevusu Olanlar</option>
                        <option value="no_appointment">Hiç Randevu Almayanlar</option>
                    </select>
                </div>
            </div>
            <div class="mb-4" id="subjectGroup">
                <label class="block text-sm font-medium text-gray-700 mb-1">Konu</label>
                <input type="text" name="subject" placeholder="Kampanya başlığı..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mesaj</label>
                <textarea name="message" rows="6" required placeholder="Mesajınızı yazın..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"></textarea>
                <p class="text-xs text-gray-400 mt-1">SMS'lerde 160 karakter sınırına dikkat edin.</p>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-md">Gönder</button>
        </form>
        <script>
        document.getElementById('msgType').addEventListener('change', function() {
            document.getElementById('subjectGroup').style.display = this.value === 'sms' ? 'none' : 'block';
        });
        </script>
