<?php

namespace App\Interfaces;

use App\Models\Booking;

interface PaymentGatewayInterface
{
    //платіжна сесія, яка повертає URL для редіректу
    public function createPaymentUrl(Booking $booking): string;
}
