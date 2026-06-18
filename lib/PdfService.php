<?php
require_once __DIR__ . '/fpdf.php';

class PdfService {
    private static function tr($text) {
        $tr = [
            'ç' => 'c', 'Ç' => 'C',
            'ğ' => 'g', 'Ğ' => 'G',
            'ı' => 'i', 'İ' => 'I',
            'ö' => 'o', 'Ö' => 'O',
            'ş' => 's', 'Ş' => 'S',
            'ü' => 'u', 'Ü' => 'U',
            'â' => 'a', 'Â' => 'A',
            'î' => 'i', 'Î' => 'I',
            'û' => 'u', 'Û' => 'U',
        ];
        return strtr($text, $tr);
    }

    public static function appointmentReceipt($appointment) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 10, self::tr('Randevu Fi?i'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, self::tr('Randevu Sistemi - ') . date('d.m.Y H:i'), 0, 1, 'R');
        $pdf->Ln(5);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, self::tr('Musteri Bilgileri'), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(50, 6, self::tr('Ad Soyad:'), 0, 0);
        $pdf->Cell(0, 6, self::tr($appointment['customer_name']), 0, 1);
        $pdf->Cell(50, 6, self::tr('E-posta:'), 0, 0);
        $pdf->Cell(0, 6, $appointment['customer_email'], 0, 1);
        $pdf->Cell(50, 6, self::tr('Telefon:'), 0, 0);
        $pdf->Cell(0, 6, $appointment['customer_phone'], 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, self::tr('Randevu Bilgileri'), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(50, 6, self::tr('Hizmet:'), 0, 0);
        $pdf->Cell(0, 6, self::tr($appointment['service_name']), 0, 1);
        $pdf->Cell(50, 6, self::tr('Tarih:'), 0, 0);
        $pdf->Cell(0, 6, date('d.m.Y', strtotime($appointment['appointment_date'])), 0, 1);
        $pdf->Cell(50, 6, self::tr('Saat:'), 0, 0);
        $pdf->Cell(0, 6, date('H:i', strtotime($appointment['appointment_time'])), 0, 1);
        $pdf->Cell(50, 6, self::tr('Tutar:'), 0, 0);
        $pdf->Cell(0, 6, number_format($appointment['price'], 0, ',', '.') . ' TL', 0, 1);
        $pdf->Cell(50, 6, self::tr('Durum:'), 0, 0);
        $statusLabels = ['pending' => 'Bekliyor', 'confirmed' => 'Onaylandi', 'cancelled' => 'Iptal', 'completed' => 'Tamamlandi'];
        $pdf->Cell(0, 6, self::tr($statusLabels[$appointment['status']] ?? $appointment['status']), 0, 1);

        if (!empty($appointment['notes'])) {
            $pdf->Ln(3);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(0, 6, self::tr('Not:'), 0, 1);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->MultiCell(0, 6, self::tr($appointment['notes']));
        }

        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(0, 5, self::tr('Bu belge otomatik olarak olusturulmustur.'), 0, 1, 'C');

        return $pdf->Output('S', 'randevu_' . $appointment['id'] . '.pdf');
    }

    public static function dailyReport($date, $appointments, $totalRevenue) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 10, self::tr('Gunluk Rapor'), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, date('d.m.Y', strtotime($date)) . ' ' . self::tr('tarihli rapor'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(10, 7, '#', 1, 0, 'C');
        $pdf->Cell(50, 7, self::tr('Musteri'), 1, 0);
        $pdf->Cell(40, 7, self::tr('Hizmet'), 1, 0);
        $pdf->Cell(20, 7, self::tr('Saat'), 1, 0, 'C');
        $pdf->Cell(25, 7, self::tr('Tutar'), 1, 0, 'R');
        $pdf->Cell(25, 7, self::tr('Durum'), 1, 0, 'C');
        $pdf->Cell(30, 7, self::tr('Calisan'), 1, 1, 'C');

        $pdf->SetFont('Helvetica', '', 9);
        $i = 1;
        foreach ($appointments as $a) {
            $statusLabels = ['pending' => 'Bekliyor', 'confirmed' => 'Onayli', 'cancelled' => 'Iptal', 'completed' => 'Tamam'];
            $pdf->Cell(10, 6, $i++, 1, 0, 'C');
            $pdf->Cell(50, 6, self::tr(mb_substr($a['customer_name'], 0, 25)), 1, 0);
            $pdf->Cell(40, 6, self::tr(mb_substr($a['service_name'], 0, 20)), 1, 0);
            $pdf->Cell(20, 6, date('H:i', strtotime($a['appointment_time'])), 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($a['price'], 0, ',', '.') . ' TL', 1, 0, 'R');
            $pdf->Cell(25, 6, $statusLabels[$a['status']] ?? $a['status'], 1, 0, 'C');
            $pdf->Cell(30, 6, self::tr(mb_substr($a['employee_name'] ?? '-', 0, 15)), 1, 1, 'C');
        }

        $pdf->Ln(5);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, self::tr('Toplam Ciro: ') . number_format($totalRevenue, 0, ',', '.') . ' TL', 0, 1, 'R');
        $pdf->Cell(0, 6, self::tr('Toplam Randevu: ') . count($appointments), 0, 1, 'R');

        return $pdf->Output('S', 'rapor_' . $date . '.pdf');
    }
}
