<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Refund;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected TicketService $ticketService,
        protected SeatReservationService $reservationService
    ) {}

    /**
     * Create a pending booking from an active seat hold with authoritative pricing.
     *
     * @param SeatHold $hold
     * @param array $customerData ['name', 'email', 'phone']
     * @param string $bookingType 'ONLINE' | 'COUNTER'
     * @param User|null $staffUser
     * @param string|null $promoCode
     * @return Booking
     * @throws Exception
     */
    public function createPendingBookingFromHold(
        SeatHold $hold,
        array $customerData,
        string $bookingType = 'ONLINE',
        ?User $staffUser = null,
        ?string $promoCode = null
    ): Booking {
        return DB::transaction(function () use ($hold, $customerData, $bookingType, $staffUser, $promoCode) {
            if ($hold->status !== 'ACTIVE' || $hold->isExpired()) {
                throw new Exception('Your seat hold has expired. Please select seats again.');
            }

            $showtime = $hold->showtime;
            $heldShowSeats = ShowSeat::with('seat')
                ->where('seat_hold_id', $hold->id)
                ->where('status', 'HELD')
                ->get();

            if ($heldShowSeats->isEmpty()) {
                throw new Exception('No seats found for this hold.');
            }

            // Calculate authoritative total
            $subtotalMinor = 0;
            $itemsData = [];

            foreach ($heldShowSeats as $showSeat) {
                $seatType = $showSeat->seat->seat_type;
                $priceMinor = $showtime->getPriceForSeatType($seatType);
                $subtotalMinor += $priceMinor;

                $itemsData[] = [
                    'seat_id' => $showSeat->seat_id,
                    'price_minor' => $priceMinor,
                ];
            }

            $discountMinor = 0;
            if ($promoCode) {
                $promo = Promotion::where('code', strtoupper(trim($promoCode)))->first();
                if ($promo && $promo->isValidForAmount($subtotalMinor)) {
                    $discountMinor = $promo->calculateDiscount($subtotalMinor);
                }
            }

            $totalMinor = max(0, $subtotalMinor - $discountMinor);

            $reference = 'BMS-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $booking = Booking::create([
                'reference' => $reference,
                'user_id' => $hold->user_id,
                'customer_name' => $customerData['name'] ?? 'Guest Customer',
                'customer_email' => $customerData['email'] ?? 'guest@example.com',
                'customer_phone' => $customerData['phone'] ?? null,
                'showtime_id' => $showtime->id,
                'status' => 'PENDING',
                'total_minor' => $totalMinor,
                'booking_type' => $bookingType,
                'created_by_staff_id' => $staffUser?->id,
                'expires_at' => $hold->expires_at,
            ]);

            foreach ($itemsData as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $item['seat_id'],
                    'price_minor' => $item['price_minor'],
                ]);
            }

            // Link booking to show_seats
            ShowSeat::where('seat_hold_id', $hold->id)
                ->update(['booking_id' => $booking->id]);

            return $booking;
        }, 5);
    }

    /**
     * Confirm a pending booking upon verified payment.
     */
    public function confirmBooking(Booking $booking, Payment $payment): Booking
    {
        return DB::transaction(function () use ($booking, $payment) {
            $booking = Booking::lockForUpdate()->find($booking->id);

            if ($booking->status === 'CONFIRMED') {
                return $booking;
            }

            $booking->update([
                'status' => 'CONFIRMED',
                'expires_at' => null,
            ]);

            // Transition seats to SOLD
            ShowSeat::query()
                ->where('booking_id', $booking->id)
                ->update([
                    'status' => 'SOLD',
                    'seat_hold_id' => null,
                    'hold_expires_at' => null,
                ]);

            // Update associated SeatHold if active
            SeatHold::query()
                ->where('showtime_id', $booking->showtime_id)
                ->where('user_id', $booking->user_id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'CONVERTED']);

            // Issue admission QR tickets
            $this->ticketService->issueTicketsForBooking($booking);

            // Audit log
            AuditLog::create([
                'actor_id' => $payment->staff_user_id ?: $booking->user_id,
                'action' => 'BOOKING_CONFIRMED',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'cinema_id' => $booking->showtime->screen->cinema_id,
                'details' => [
                    'reference' => $booking->reference,
                    'total_minor' => $booking->total_minor,
                    'payment_method' => $payment->method,
                    'transaction_ref' => $payment->transaction_reference,
                ],
            ]);

            return $booking;
        }, 5);
    }

    /**
     * Cancel booking and release seats back to inventory.
     */
    public function cancelBooking(Booking $booking, string $reason = 'Customer cancellation', ?User $actor = null): bool
    {
        return DB::transaction(function () use ($booking, $reason, $actor) {
            $booking = Booking::lockForUpdate()->find($booking->id);

            if ($booking->status === 'CANCELLED') {
                return true;
            }

            $wasConfirmed = ($booking->status === 'CONFIRMED');

            $booking->update(['status' => 'CANCELLED']);

            // Release seats back to AVAILABLE
            ShowSeat::query()
                ->where('booking_id', $booking->id)
                ->update([
                    'status' => 'AVAILABLE',
                    'seat_hold_id' => null,
                    'booking_id' => null,
                    'hold_expires_at' => null,
                ]);

            // Invalidate any issued tickets
            foreach ($booking->items as $item) {
                if ($item->ticket) {
                    $item->ticket->update(['status' => 'CANCELLED']);
                }
            }

            // If confirmed and paid, record refund
            if ($wasConfirmed) {
                $payment = $booking->latestPayment;
                if ($payment && $payment->status === 'SUCCESS') {
                    Refund::create([
                        'payment_id' => $payment->id,
                        'booking_id' => $booking->id,
                        'amount_minor' => $booking->total_minor,
                        'status' => 'COMPLETED',
                        'reason' => $reason,
                        'approved_by_user_id' => $actor?->id,
                    ]);
                }
            }

            AuditLog::create([
                'actor_id' => $actor?->id,
                'action' => 'BOOKING_CANCELLED',
                'entity_type' => 'Booking',
                'entity_id' => $booking->id,
                'cinema_id' => $booking->showtime->screen->cinema_id,
                'details' => [
                    'reference' => $booking->reference,
                    'reason' => $reason,
                    'was_confirmed' => $wasConfirmed,
                ],
            ]);

            return true;
        }, 5);
    }
}

