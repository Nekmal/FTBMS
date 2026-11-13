<?php

namespace App\Payments;

use App\Models\Booking;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function charge(Booking $booking, array $params = []): PaymentResult
    {
        $reference = 'MOCK-PAY-' . strtoupper(Str::random(12));
        $outcome = $params['simulate_outcome'] ?? 'SUCCESS';

        if ($outcome === 'FAILURE') {
            return PaymentResult::failure($reference, 'Simulated payment rejection: Insufficient funds or card declined.', [
                'provider' => 'MockGateway',
                'booking_ref' => $booking->reference,
                'amount_minor' => $booking->total_minor,
                'currency' => 'LKR',
            ]);
        }

        if ($outcome === 'PENDING') {
            return PaymentResult::pending($reference, 'Payment awaiting 3D Secure verification.', [
                'provider' => 'MockGateway',
                'booking_ref' => $booking->reference,
                'amount_minor' => $booking->total_minor,
                'currency' => 'LKR',
            ]);
        }

        return PaymentResult::success($reference, 'Online payment successfully simulated and verified.', [
            'provider' => 'MockGateway',
            'booking_ref' => $booking->reference,
            'amount_minor' => $booking->total_minor,
            'currency' => 'LKR',
            'authorized_at' => now()->toIso8601String(),
        ]);
    }

    public function verify(string $transactionReference): PaymentResult
    {
        return PaymentResult::success($transactionReference, 'Transaction verified.');
    }
}
