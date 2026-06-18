<?php
require_once __DIR__ . '/Settings.php';

class SmsService {
    public static function send($phone, $message) {
        $config = Settings::getSmsConfig();
        $phone = self::normalizePhone($phone);
        switch ($config['provider']) {
            case 'twilio':
                return self::sendTwilio($phone, $message);
            case 'netgsm':
                return self::sendNetGsm($phone, $message);
            default:
                return self::sendLog($phone, $message);
        }
    }

    private static function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) === '05') $phone = '+90' . substr($phone, 1);
        elseif (substr($phone, 0, 3) === '905') $phone = '+' . $phone;
        elseif (strlen($phone) === 10) $phone = '+90' . $phone;
        return $phone;
    }

    private static function sendLog($phone, $message) {
        $log = "[SMS] To: $phone | Message: $message";
        error_log($log);
        file_put_contents(__DIR__ . '/../assets/sms_log.txt', date('Y-m-d H:i:s') . " $log\n", FILE_APPEND);
        return true;
    }

    private static function sendTwilio($phone, $message) {
        $c = Settings::getSmsConfig();
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$c['twilio_sid']}/Messages.json";
        $data = http_build_query([
            'From' => $c['twilio_from'],
            'To' => $phone,
            'Body' => $message,
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERPWD, $c['twilio_sid'] . ':' . $c['twilio_token']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result !== false;
    }

    private static function sendNetGsm($phone, $message) {
        $c = Settings::getSmsConfig();
        $params = http_build_query([
            'usercode' => $c['netgsm_user'],
            'password' => $c['netgsm_pass'],
            'msgheader' => $c['netgsm_msgheader'],
            'gsm' => $phone,
            'message' => $message,
        ]);
        $result = @file_get_contents("https://api.netgsm.com.tr/sms/send/get?$params");
        return $result !== false;
    }

    public static function sendAppointmentConfirmation($appointment) {
        $date = date('d.m.Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        $msg = "Randevunuz olusturuldu: {$appointment['service_name']} - $date $time";
        return self::send($appointment['customer_phone'], $msg);
    }

    public static function sendStatusUpdate($appointment) {
        $labels = ['confirmed' => 'onaylandi', 'cancelled' => 'iptal edildi', 'completed' => 'tamamlandi'];
        $label = $labels[$appointment['status']] ?? $appointment['status'];
        $msg = "Randevunuz $label: {$appointment['service_name']}";
        return self::send($appointment['customer_phone'], $msg);
    }
}
