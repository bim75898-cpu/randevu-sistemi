<?php
class CalendarService {
    private static $config = [
        'google_client_id' => '',
        'google_client_secret' => '',
        'google_redirect_uri' => '',
        'enabled' => false,
    ];

    public static function generateGoogleCalendarLink($appointment) {
        $start = date('Ymd\THis', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
        $duration = (int)($appointment['duration'] ?? 60);
        $end = date('Ymd\THis', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) + $duration * 60);
        $name = rawurlencode($appointment['service_name'] . ' - Randevu');
        $details = rawurlencode("Randevu: {$appointment['service_name']}\nMüşteri: {$appointment['customer_name']}");

        $url = "https://www.google.com/calendar/render?action=TEMPLATE&text=$name&dates=$start/$end&details=$details";
        return $url;
    }

    public static function generateOutlookLink($appointment) {
        $start = date('Y-m-d\TH:i:s', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
        $duration = (int)($appointment['duration'] ?? 60);
        $end = date('Y-m-d\TH:i:s', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) + $duration * 60);
        $name = rawurlencode($appointment['service_name'] . ' - Randevu');

        $url = "https://outlook.live.com/calendar/0/deeplink/compose?subject=$name&startdt=$start&enddt=$end";
        return $url;
    }

    public static function renderCalendarButtons($appointment) {
        $gcal = self::generateGoogleCalendarLink($appointment);
        $outlook = self::generateOutlookLink($appointment);
        return "
        <div class='flex gap-2 mt-3'>
            <a href='$gcal' target='_blank' class='flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all'>
                <svg class='w-4 h-4' viewBox='0 0 24 24' fill='#4285F4'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z'/><path fill='#fff' d='M10.5 16.5l-3-3 1.06-1.06 1.94 1.94 4.94-4.94 1.06 1.06z'/></svg>
                Google Takvim
            </a>
            <a href='$outlook' target='_blank' class='flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all'>
                <svg class='w-4 h-4' viewBox='0 0 24 24' fill='#0078D4'><path d='M21 3H3v18h18V3zM12 12c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 4c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z'/></svg>
                Outlook
            </a>
        </div>";
    }
}
