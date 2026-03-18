<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePaymentService implements PaymentGatewayInterface
{
    public function createPaymentUrl(Booking $booking): string
    {
        Stripe::setApiKey(config('services.stripe.secret')); // секретний ключ
        $customerEmail = $booking->guest_email ?? $booking->user?->email;
        $sessionConfig = [ // створюємо сесію в Stripe
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'uah',
                    'product_data' => [
                        'name' => 'Квест: ' . $booking->room->name,
                    ],
                    'unit_amount' => $booking->total_price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'http://localhost:5173/success',
            'cancel_url' => 'http://localhost:5173/cancel',
            'metadata' => [
                'booking_id' => $booking->id,
            ],
            'billing_address_collection' => 'auto',
        ];
        if ($customerEmail) {
            $sessionConfig['customer_email'] = $customerEmail;
        }
        $session = Session::create($sessionConfig);
        $booking->payment()->updateOrCreate( // зберігаємо транзакцію в БД
            ['booking_id' => $booking->id],
            [
                'transaction_id' => $session->id,
                'amount' => $booking->total_price,
                'currency' => 'uah',
                'status' => 'pending',
                'payment_method' => 'stripe'
            ]
        );
        return $session->url;
    }
}
