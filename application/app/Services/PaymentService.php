<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Payments\MockPaymentGateway;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected MockPaymentGateway $gateway,
        protected BookingService $bookingService
    ) {}

    /**
     * Process online simulated payment for a booking.
     *
     * @param Booking $booking
     * @param string $simulateOutcome 'SUCCESS' | 'FAILURE' | 'PENDING'
     * @return Payment
     * @throws Exception
     */
    public function processOnlineMockPayment(Booking $booking, string $simulateOutcome = 'SUCCESS'): Payment
    {
        return DB::transaction(function () use ($booking, $simulateOutcome) {
            $booking = Booking::lockForUpdate()->find($booking->id);

            if ($booking->status === 'CONFIRMED') {
                throw new Exception('This booking is already paid and confirmed.');
            }

            if ($booking->status === 'CANCELLED' || $booking->status === 'EXPIRED') {
                throw new Exception("Cannot pay for a {$booking->status} booking.");
            }

            // Charge through mock gateway
            $result = $this->gateway->charge($booking, ['simulate_outcome' => $simulateOutcome]);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'method' => 'ONLINE_MOCK',
                'amount_minor' => $booking->total_minor,
                'status' => $result->status,
                'transaction_reference' => $result->transactionReference,
                'payload' => $result->rawPayload,
            ]);

            if ($result->successful) {
                $this->bookingService->confirmBooking($booking, $payment);
            }

            return $payment;
        }, 5);
    }
}

