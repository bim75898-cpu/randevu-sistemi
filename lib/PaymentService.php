<?php
require_once __DIR__ . '/Settings.php';

class PaymentService {
    public static function createCheckoutForm($appointment, $callbackUrl) {
        $c = Settings::getPaymentConfig();
        $price = number_format($appointment['price'], 2, '.', '');

        return [
            'status' => 'success',
            'token' => 'sandbox_token_' . $appointment['id'],
            'checkoutFormUrl' => $c['iyzico_sandbox']
                ? 'https://sandbox.iyzipay.com/payment?token=sandbox_token_' . $appointment['id']
                : 'https://api.iyzipay.com/payment?token=token_' . $appointment['id'],
            'htmlContent' => '<div id="iyzipay-checkout-form" class="pay-button">Ödeme Yap</div>',
        ];
    }

    public static function createPaymentPage($appointment) {
        $baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/randv";
        $result = self::createCheckoutForm($appointment, "$baseUrl/customer/payment_callback.php");

        $price = number_format($appointment['price'], 0, ',', '.');
        $date = date('d.m.Y', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        $modeLabel = (Settings::get('payment_iyzico_sandbox', '1') === '1') ? 'Sandbox (Test)' : 'Canlı';

        $html = "
        <div class='bg-white rounded-2xl shadow-xl border border-gray-100 p-8'>
            <div class='text-center mb-6'>
                <div class='w-16 h-16 bg-gradient-to-br from-indigo-100 to-violet-100 rounded-2xl flex items-center justify-center mx-auto mb-4'>
                    <svg class='w-8 h-8 text-indigo-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'/></svg>
                </div>
                <h1 class='text-xl font-bold text-gray-900'>Online Ödeme</h1>
                <p class='text-gray-500 text-sm mt-1'>Randevunuzu guvence altina almak icin odeyin.</p>
            </div>

            <div class='bg-gray-50 rounded-xl p-4 mb-6 space-y-2'>
                <div class='flex justify-between text-sm'>
                    <span class='text-gray-500'>Hizmet</span>
                    <span class='font-medium text-gray-900'>{$appointment['service_name']}</span>
                </div>
                <div class='flex justify-between text-sm'>
                    <span class='text-gray-500'>Tarih</span>
                    <span class='font-medium text-gray-900'>$date</span>
                </div>
                <div class='flex justify-between text-sm'>
                    <span class='text-gray-500'>Saat</span>
                    <span class='font-medium text-gray-900'>$time</span>
                </div>
                <div class='flex justify-between text-sm pt-2 border-t border-gray-200'>
                    <span class='text-gray-700 font-semibold'>Tutar</span>
                    <span class='text-xl font-bold text-indigo-600'>$price TL</span>
                </div>
            </div>

            <div class='flex flex-col gap-3'>
                <a href='$result[checkoutFormUrl]' target='_blank' class='w-full py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all shadow-lg text-center'>Kart ile Ode</a>
                <a href='../index.php' class='w-full py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition-all text-center text-sm'>Sonra Ode</a>
            </div>
            <p class='text-xs text-gray-400 text-center mt-4'>Odeme saglayici: Iyzico ($modeLabel)</p>
        </div>";
        return $html;
    }

    public static function processCallback($postData) {
        return [
            'status' => 'success',
            'paymentId' => 'sandbox_pay_' . ($postData['conversationId'] ?? '0'),
        ];
    }
}
