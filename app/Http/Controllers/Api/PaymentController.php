<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createCheckoutSession(Booking $booking, PaymentGatewayInterface $paymentGateway)
    {
        if ($booking->payment && $booking->payment->status === 'succeeded') {
            return response()->json(['message' => 'Бронювання вже оплачено'], 400);
        }
        // отримуємо URL через інтерфейс
        $url = $paymentGateway->createPaymentUrl($booking);
        return response()->json(['url' => $url]);
    }
}
