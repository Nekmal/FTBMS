<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashPaymentService
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Record cash received at counter and immediately confirm booking.
     *
     * @param Booking $booking
     * @param User $staffUser
     * @param int $cashReceivedMinor
     * @return Payment
     * @throws Exception
     */
    public function recordCashPayment(Booking $booking, User $staffUser, int $cashReceivedMinor): Payment
    {
        $cinemaId = $booking->showtime->screen->cinema_id;

        if (!$staffUser->isAdmin() && !$staffUser->isCounterStaff($cinemaId)) {
            throw new Exception('Unauthorized: You are not assigned as counter staff to this cinema branch.');
        }

        if ($cashReceivedMinor < $booking->total_minor) {
            $shortage = ($booking->total_minor - $cashReceivedMinor) / 100;
            throw new Exception("Insufficient cash received. Shortage: Rs. " . number_format($shortage, 2));
        }

        $changeMinor = $cashReceivedMinor - $booking->total_minor;

        return DB::transaction(function () use ($booking, $staffUser, $cashReceivedMinor, $changeMinor, $cinemaId) {
            $booking = Booking::lockForUpdate()->find($booking->id);

            if ($booking->status === 'CONFIRMED') {
                throw new Exception('This booking has already been paid and confirmed.');
            }

            $receiptRef = 'CASH-REC-' . date('Ymd') . '-' . strtoupper(Str::random(8));

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'method' => 'CASH',
                'amount_minor' => $booking->total_minor,
                'status' => 'SUCCESS',
                'transaction_reference' => $receiptRef,
                'staff_user_id' => $staffUser->id,
                'cash_received_minor' => $cashReceivedMinor,
                'cash_returned_minor' => $changeMinor,
                'payload' => [
                    'branch_cinema_id' => $cinemaId,
                    'collected_by_name' => $staffUser->name,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            $this->bookingService->confirmBooking($booking, $payment);

            AuditLog::create([
                'actor_id' => $staffUser->id,
                'action' => 'COUNTER_CASH_COLLECTED',
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'cinema_id' => $cinemaId,
                'details' => [
                    'receipt_ref' => $receiptRef,
                    'booking_ref' => $booking->reference,
                    'total_minor' => $booking->total_minor,
                    'received_minor' => $cashReceivedMinor,
                    'change_minor' => $changeMinor,
                ],
            ]);

            return $payment;
        }, 5);
    }
}
