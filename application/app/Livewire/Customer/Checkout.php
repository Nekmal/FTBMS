<?php

namespace App\Livewire\Customer;

use App\Models\Booking;
use App\Models\Promotion;
use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\SeatReservationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Checkout extends Component
{
    public SeatHold $hold;
    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerPhone = '';
    public string $promoCode = '';
    public ?string $appliedPromoCode = null;
    public int $discountMinor = 0;
    public string $simulationOutcome = 'SUCCESS'; // 'SUCCESS' or 'FAILURE'

    public ?string $errorMessage = null;
    public ?string $successMessage = null;
    public bool $isProcessing = false;

    public function mount(SeatHold $hold)
    {
        if ($hold->status !== 'ACTIVE' || $hold->isExpired()) {
            return redirect()->route('movies.index')->with('error', 'Your seat hold has expired. Please select seats again.');
        }

        $this->hold = $hold->load(['showtime.movie', 'showtime.screen.cinema', 'showtime.prices']);

        $user = Auth::user();
        if ($user) {
            $this->customerName = $user->name;
            $this->customerEmail = $user->email;
        }
    }

    public function applyPromotion()
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $code = strtoupper(trim($this->promoCode));
        if (empty($code)) {
            $this->errorMessage = 'Please enter a promotion code.';
            return;
        }

        $promo = Promotion::where('code', $code)->first();
        if (!$promo) {
            $this->errorMessage = 'Invalid promotion code.';
            return;
        }

        $subtotal = $this->getSubtotalMinor();
        if (!$promo->isValidForAmount($subtotal)) {
            $minRs = number_format($promo->min_spend_minor / 100, 2);
            $this->errorMessage = "Promotion code is not valid for this order (Minimum spend: Rs. {$minRs}).";
            return;
        }

        $this->discountMinor = $promo->calculateDiscount($subtotal);
        $this->appliedPromoCode = $promo->code;
        $this->successMessage = "Promo code '{$promo->code}' applied successfully!";
    }

    public function removePromotion()
    {
        $this->promoCode = '';
        $this->appliedPromoCode = null;
        $this->discountMinor = 0;
        $this->successMessage = null;
    }

    public function getHeldSeatsProperty()
    {
        return ShowSeat::with('seat')
            ->where('seat_hold_id', $this->hold->id)
            ->where('status', 'HELD')
            ->get();
    }

    public function getSubtotalMinor(): int
    {
        $total = 0;
        $showtime = $this->hold->showtime;
        foreach ($this->heldSeats as $showSeat) {
            $total += $showtime->getPriceForSeatType($showSeat->seat->seat_type);
        }
        return $total;
    }

    public function getFinalTotalMinor(): int
    {
        return max(0, $this->getSubtotalMinor() - $this->discountMinor);
    }

    public function submitPayment(BookingService $bookingService, PaymentService $paymentService)
    {
        $this->errorMessage = null;

        $this->validate([
            'customerName' => 'required|string|min:3|max:100',
            'customerEmail' => 'required|email|max:100',
            'customerPhone' => 'nullable|string|max:20',
        ]);

        if ($this->hold->isExpired()) {
            $this->errorMessage = 'Your 7-minute reservation hold has expired. Please select seats again.';
            return;
        }

        $this->isProcessing = true;

        try {
            // 1. Create Pending Booking with Authoritative Server Pricing
            $booking = $bookingService->createPendingBookingFromHold(
                $this->hold,
                [
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'phone' => $this->customerPhone,
                ],
                'ONLINE',
                null,
                $this->appliedPromoCode
            );

            // 2. Process Online Payment via Mock Gateway
            $payment = $paymentService->processOnlineMockPayment(
                $booking,
                $this->simulationOutcome
            );

            if ($payment->status === 'SUCCESS') {
                return redirect()->route('bookings.confirmation', ['booking' => $booking->id]);
            } else {
                $this->errorMessage = 'Payment declined by gateway: Simulated insufficient funds or bank rejection. Your seat hold is still active; you can test again with "Success" selected.';
                $this->isProcessing = false;
            }
        } catch (Exception $e) {
            $this->errorMessage = 'Checkout failed: ' . $e->getMessage();
            $this->isProcessing = false;
        }
    }

    public function cancelReservation(SeatReservationService $reservationService)
    {
        $reservationService->releaseHold($this->hold);
        return redirect()->route('movies.show', $this->hold->showtime->movie->slug);
    }

    public function render()
    {
        $secondsRemaining = max(0, $this->hold->expires_at->diffInSeconds(now()));

        return view('livewire.customer.checkout', [
            'subtotalMinor' => $this->getSubtotalMinor(),
            'finalTotalMinor' => $this->getFinalTotalMinor(),
            'secondsRemaining' => $secondsRemaining,
        ])->layout('layouts.app');
    }
}

