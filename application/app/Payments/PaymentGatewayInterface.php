<?php

namespace App\Payments;

use App\Models\Booking;

interface PaymentGatewayInterface
{
    public function charge(Booking $booking, array $params = []): PaymentResult;

    public function verify(string $transactionReference): PaymentResult;
}
