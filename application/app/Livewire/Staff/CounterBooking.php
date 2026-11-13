<?php

namespace App\Livewire\Staff;

use App\Models\Cinema;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Services\BookingService;
use App\Services\CashPaymentService;
use App\Services\SeatReservationService;
use App\Services\SeatUnavailableException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CounterBooking extends Component
{
    public ?int $selectedCinemaId = null;
    public ?int $selectedShowtimeId = null;
    public array $selectedSeatIds = [];
    public ?SeatHold $activeHold = null;

    public string $customerName = 'Walk-in Customer';
    public string $customerEmail = 'counter@bms.lk';
    public string $customerPhone = '';
    public string $cashReceivedInput = '';

    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    public function mount()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            $firstCinema = Cinema::first();
            $this->selectedCinemaId = $firstCinema?->id;
        } else {
            $assigned = $user->assignedCinema;
            $this->selectedCinemaId = $assigned?->id;
        }

        $this->loadDefaultShowtime();
    }

    public function loadDefaultShowtime()
    {
        if ($this->selectedCinemaId) {
            $showtime = Showtime::whereHas('screen', fn($q) => $q->where('cinema_id', $this->selectedCinemaId))
                ->whereDate('starts_at', Carbon::today())
                ->where('status', 'SCHEDULED')
                ->orderBy('starts_at')
                ->first();

            $this->selectedShowtimeId = $showtime?->id;
        }
    }

    public function updatedSelectedCinemaId()
    {
        $this->selectedShowtimeId = null;
        $this->selectedSeatIds = [];
        $this->activeHold = null;
        $this->loadDefaultShowtime();
    }

    public function updatedSelectedShowtimeId()
    {
        $this->selectedSeatIds = [];
        $this->activeHold = null;
        $this->errorMessage = null;
    }

    public function toggleSeat(int $seatId)
    {
        $this->errorMessage = null;

        if (in_array($seatId, $this->selectedSeatIds)) {
            $this->selectedSeatIds = array_values(array_diff($this->selectedSeatIds, [$seatId]));
        } else {
            $this->selectedSeatIds[] = $seatId;
        }
    }

    public function holdSeats(SeatReservationService $reservationService)
    {
        $this->errorMessage = null;

        if (empty($this->selectedSeatIds)) {
            $this->errorMessage = 'Please select seats for the counter sale.';
            return;
        }

        try {
            $this->activeHold = $reservationService->createHold(
                $this->selectedShowtimeId,
                $this->selectedSeatIds,
                Auth::user()
            );

            // Pre-fill cash received with total
            $this->cashReceivedInput = (string) ($this->getTotalMinor() / 100);
        } catch (SeatUnavailableException $e) {
            $this->errorMessage = $e->getMessage();
            $this->selectedSeatIds = [];
        } catch (Exception $e) {
            $this->errorMessage = 'Hold failed: ' . $e->getMessage();
        }
    }

    public function getTotalMinor(): int
    {
        if (!$this->selectedShowtimeId) return 0;

        $showtime = Showtime::find($this->selectedShowtimeId);
        $total = 0;
        $seats = Seat::whereIn('id', $this->selectedSeatIds)->get();

        foreach ($seats as $seat) {
            $total += $showtime->getPriceForSeatType($seat->seat_type);
        }

        return $total;
    }

    public function getChangeMinor(): int
    {
        $received = (int) (floatval($this->cashReceivedInput) * 100);
        return max(0, $received - $this->getTotalMinor());
    }

    public function confirmCashSale(
        BookingService $bookingService,
        CashPaymentService $cashPaymentService
    ) {
        $this->errorMessage = null;

        if (!$this->activeHold) {
            $this->errorMessage = 'Please hold seats first before recording cash.';
            return;
        }

        $receivedMinor = (int) (floatval($this->cashReceivedInput) * 100);
        $totalMinor = $this->getTotalMinor();

        if ($receivedMinor < $totalMinor) {
            $this->errorMessage = 'Cash received is less than total amount due.';
            return;
        }

        try {
            // 1. Create Pending Counter Booking
            $booking = $bookingService->createPendingBookingFromHold(
                $this->activeHold,
                [
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'phone' => $this->customerPhone,
                ],
                'COUNTER',
                Auth::user()
            );

            // 2. Record Cash Payment & Confirm
            $cashPaymentService->recordCashPayment(
                $booking,
                Auth::user(),
                $receivedMinor
            );

            return redirect()->route('bookings.confirmation', ['booking' => $booking->id])
                ->with('success', 'Counter cash sale completed and tickets issued!');
        } catch (Exception $e) {
            $this->errorMessage = 'Cash sale failed: ' . $e->getMessage();
        }
    }

    public function render(SeatReservationService $reservationService)
    {
        $user = Auth::user();

        $cinemas = $user->isAdmin()
            ? Cinema::where('is_active', true)->get()
            : Cinema::where('id', $this->selectedCinemaId)->get();

        $todayShowtimes = collect();
        if ($this->selectedCinemaId) {
            $todayShowtimes = Showtime::with(['movie', 'screen'])
                ->whereHas('screen', fn($q) => $q->where('cinema_id', $this->selectedCinemaId))
                ->whereDate('starts_at', Carbon::today())
                ->orderBy('starts_at')
                ->get();
        }

        $selectedShowtime = $this->selectedShowtimeId ? Showtime::with(['layout.seats', 'prices'])->find($this->selectedShowtimeId) : null;

        $inventory = collect();
        $seatsGroupedByRow = collect();

        if ($selectedShowtime) {
            $inventory = $reservationService->getShowtimeSeatMap($selectedShowtime->id)->keyBy('seat_id');
            $seatsGroupedByRow = Seat::where('layout_id', $selectedShowtime->layout_id)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get()
                ->groupBy('row_label');
        }

        return view('livewire.staff.counter-booking', [
            'cinemas' => $cinemas,
            'todayShowtimes' => $todayShowtimes,
            'selectedShowtime' => $selectedShowtime,
            'inventory' => $inventory,
            'seatsGroupedByRow' => $seatsGroupedByRow,
            'totalMinor' => $this->getTotalMinor(),
            'changeMinor' => $this->getChangeMinor(),
        ])->layout('layouts.app');
    }
}

