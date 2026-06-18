<?php
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/Settings.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {
    public static function send($to, $subject, $body, $cc = null) {
        $config = Settings::getMailConfig();
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->Port = $config['smtp_port'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->SMTPAuth = $config['smtp_auth'];
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to);
            if ($cc) $mail->addCC($cc);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendAppointmentConfirmation($appointment, $cancelToken) {
        $date = date('d.m.Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        $baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/randv";
        $cancelUrl = "$baseUrl/customer/cancel.php?token=$cancelToken";
        $checkinUrl = "$baseUrl/checkin.php?token=$cancelToken";

        $subject = "Randevu Onayı - {$appointment['service_name']}";
        $body = "
        <div style='max-width:600px;margin:0 auto;font-family:sans-serif'>
            <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:30px;border-radius:12px 12px 0 0;text-align:center'>
                <h1 style='color:#fff;margin:0;font-size:24px'>✅ Randevu Oluşturuldu</h1>
            </div>
            <div style='background:#fff;padding:30px;border:1px solid #e5e7eb;border-radius:0 0 12px 12px'>
                <p style='color:#374151;font-size:16px'>Merhaba <strong>{$appointment['customer_name']}</strong>,</p>
                <p style='color:#374151'>Randevunuz başarıyla oluşturuldu.</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0'>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Hizmet</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>{$appointment['service_name']}</td></tr>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Tarih</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>$date</td></tr>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Saat</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>$time</td></tr>
                    <tr><td style='padding:10px;color:#6b7280'>Tutar</td><td style='padding:10px;font-weight:600'>" . number_format($appointment['price'], 0, ',', '.') . " TL</td></tr>
                </table>
                <p style='color:#374151'>Randevunuz admin tarafından onaylandıktan sonra ayrıca bilgilendirileceksiniz.</p>
                <div style='text-align:center;margin:25px 0;display:flex;flex-direction:column;gap:10px'>
                    <a href='$checkinUrl' style='display:inline-block;padding:12px 24px;background:#6366f1;color:#fff;text-decoration:none;border-radius:8px;font-size:14px'>🚪 Check-In Yap</a>
                    <a href='$cancelUrl' style='display:inline-block;padding:12px 24px;background:#fee2e2;color:#dc2626;text-decoration:none;border-radius:8px;font-size:14px'>Randevuyu İptal Et</a>
                </div>
                <p style='color:#9ca3af;font-size:12px;text-align:center;margin-top:20px'>Bu e-posta otomatik gönderilmiştir.</p>
            </div>
        </div>";
        return self::send($appointment['customer_email'], $subject, $body);
    }

    public static function sendAppointmentCompleted($appointment) {
        $baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/randv";
        $reviewUrl = "$baseUrl/review.php?token={$appointment['token']}";

        $subject = "Deneyiminizi Değerlendirin - {$appointment['service_name']}";
        $body = "
        <div style='max-width:600px;margin:0 auto;font-family:sans-serif'>
            <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:30px;border-radius:12px 12px 0 0;text-align:center'>
                <h1 style='color:#fff;margin:0;font-size:24px'>Nasıldı?</h1>
            </div>
            <div style='background:#fff;padding:30px;border:1px solid #e5e7eb;border-radius:0 0 12px 12px'>
                <p style='color:#374151;font-size:16px'>Merhaba <strong>{$appointment['customer_name']}</strong>,</p>
                <p style='color:#374151'>{$appointment['service_name']} randevunuz tamamlandı. Deneyiminizi değerlendirir misiniz?</p>
                <div style='text-align:center;margin:25px 0'>
                    <a href='$reviewUrl' style='display:inline-block;padding:14px 32px;background:#6366f1;color:#fff;text-decoration:none;border-radius:8px;font-size:16px'>Değerlendir</a>
                </div>
                <p style='color:#9ca3af;font-size:12px;text-align:center;margin-top:20px'>Görüşleriniz bizim için çok değerli.</p>
            </div>
        </div>";
        return self::send($appointment['customer_email'], $subject, $body);
    }

    public static function sendStatusUpdate($appointment) {
        $date = date('d.m.Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        $statusLabels = ['confirmed' => 'Onaylandı', 'cancelled' => 'İptal Edildi', 'completed' => 'Tamamlandı'];
        $statusColors = ['confirmed' => '#22c55e', 'cancelled' => '#ef4444', 'completed' => '#3b82f6'];
        $label = $statusLabels[$appointment['status']] ?? $appointment['status'];
        $color = $statusColors[$appointment['status']] ?? '#6366f1';

        $subject = "Randevu $label - {$appointment['service_name']}";
        $body = "
        <div style='max-width:600px;margin:0 auto;font-family:sans-serif'>
            <div style='background:$color;padding:30px;border-radius:12px 12px 0 0;text-align:center'>
                <h1 style='color:#fff;margin:0;font-size:24px'>Randevu $label</h1>
            </div>
            <div style='background:#fff;padding:30px;border:1px solid #e5e7eb;border-radius:0 0 12px 12px'>
                <p style='color:#374151;font-size:16px'>Merhaba <strong>{$appointment['customer_name']}</strong>,</p>
                <p style='color:#374151'>Randevunuz <strong>$label</strong>.</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0'>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Hizmet</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>{$appointment['service_name']}</td></tr>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Tarih</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>$date</td></tr>
                    <tr><td style='padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280'>Saat</td><td style='padding:10px;border-bottom:1px solid #f3f4f6;font-weight:600'>$time</td></tr>
                </table>
            </div>
        </div>";
        return self::send($appointment['customer_email'], $subject, $body);
    }
}
