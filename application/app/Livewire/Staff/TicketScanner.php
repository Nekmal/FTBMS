<?php

namespace App\Livewire\Staff;

use App\Models\Cinema;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TicketScanner extends Component
{
    public string $scanInput = '';
    public ?array $scanResult = null;
    public ?string $errorMessage = null;

    public function validateCode(TicketService $ticketService)
    {
        $this->errorMessage = null;
        $this->scanResult = null;

        $token = trim($this->scanInput);
        if (empty($token)) {
            $this->errorMessage = 'Please enter or scan a ticket QR token or code.';
            return;
        }

        $result = $ticketService->validateTicket($token, Auth::user());
        $this->scanResult = $result;

        // Clear input field on success for rapid scanning
        if ($result['valid']) {
            $this->scanInput = '';
        }
    }

    public function testScanTicket(string $code)
    {
        $this->scanInput = $code;
        $this->validateCode(app(TicketService::class));
    }

    public function render()
    {
        $user = Auth::user();
        $cinemaId = $user->assignedCinema?->id;

        // Get latest 5 issued tickets for easy 1-click testing in demonstration
        $recentTickets = Ticket::with(['bookingItem.booking.showtime.movie', 'bookingItem.seat'])
            ->when(!$user->isAdmin() && $cinemaId, function ($q) use ($cinemaId) {
                $q->whereHas('bookingItem.booking.showtime.screen', fn($sq) => $sq->where('cinema_id', $cinemaId));
            })
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.staff.ticket-scanner', [
            'recentTickets' => $recentTickets,
        ])->layout('layouts.app');
    }
}

