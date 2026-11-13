<?php

namespace App\Livewire\Customer;

use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Services\SeatReservationService;
use App\Services\SeatUnavailableException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class SeatSelection extends Component
{
    public Showtime $showtime;
    public array $selectedSeatIds = [];
    public ?string $sessionToken = null;
    public ?string $errorMessage = null;
    public ?int $activeHoldId = null;
    public int $totalMinor = 0;

    public function mount(Showtime $showtime)
    {
        $this->showtime = $showtime->load(['movie', 'screen.cinema', 'prices', 'layout.seats']);

        if (!Session::has('bms_session_token')) {
            Session::put('bms_session_token', bin2hex(random_bytes(16)));
        }
        $this->sessionToken = Session::get('bms_session_token');
    }

    public function toggleSeat(int $seatId)
    {
        $this->errorMessage = null;

        if (in_array($seatId, $this->selectedSeatIds)) {
            $this->selectedSeatIds = array_values(array_diff($this->selectedSeatIds, [$seatId]));
        } else {
            if (count($this->selectedSeatIds) >= 8) {
                $this->errorMessage = 'Maximum 8 seats can be selected per transaction.';
                return;
            }

            // Check if seat is currently available
            $showSeat = ShowSeat::where('showtime_id', $this->showtime->id)
                ->where('seat_id', $seatId)
                ->first();

            if (!$showSeat || !$showSeat->isAvailable()) {
                $this->errorMessage = 'This seat is not available.';
                return;
            }

            $this->selectedSeatIds[] = $seatId;
        }

        $this->recalculateTotal();
    }

    public function recalculateTotal(): void
    {
        $total = 0;
        $seats = Seat::whereIn('id', $this->selectedSeatIds)->get();

        foreach ($seats as $seat) {
            $total += $this->showtime->getPriceForSeatType($seat->seat_type);
        }

        $this->totalMinor = $total;
    }

    public function proceedToHold(SeatReservationService $reservationService)
    {
        $this->errorMessage = null;

        if (empty($this->selectedSeatIds)) {
            $this->errorMessage = 'Please select at least one seat to proceed.';
            return;
        }

        try {
            $hold = $reservationService->createHold(
                $this->showtime->id,
                $this->selectedSeatIds,
                Auth::user(),
                $this->sessionToken
            );

            $this->activeHoldId = $hold->id;

            return redirect()->route('checkout.show', ['hold' => $hold->id]);
        } catch (SeatUnavailableException $e) {
            $this->errorMessage = $e->getMessage() . ' The seat map has been updated.';
            $this->selectedSeatIds = [];
            $this->recalculateTotal();
        } catch (Exception $e) {
            $this->errorMessage = 'An error occurred while reserving seats: ' . $e->getMessage();
        }
    }

    public function render(SeatReservationService $reservationService)
    {
        // Fetch seat inventory with expired hold cleanup
        $inventory = $reservationService->getShowtimeSeatMap($this->showtime->id)
            ->keyBy('seat_id');

        $layout = $this->showtime->layout;
        $seatsGroupedByRow = Seat::where('layout_id', $layout->id)
            ->where('is_active', true)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        return view('livewire.customer.seat-selection', [
            'seatsGroupedByRow' => $seatsGroupedByRow,
            'inventory' => $inventory,
        ])->layout('layouts.app');
    }
}

