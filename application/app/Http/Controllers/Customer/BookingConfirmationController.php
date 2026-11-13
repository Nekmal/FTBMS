<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingConfirmationController extends Controller
{
    public function show(Booking $booking, TicketService $ticketService)
    {
        $booking->load([
            'showtime.movie',
            'showtime.screen.cinema',
            'items.seat',
            'items.ticket',
            'latestPayment',
            'refunds',
        ]);

        // Generate QR code SVGs for each ticket
        $ticketsWithQr = [];
        foreach ($booking->items as $item) {
            if ($item->ticket) {
                $qrSvg = $ticketService->generateQrCodeSvg($item->ticket->qr_token_hash);
                $ticketsWithQr[] = [
                    'ticket' => $item->ticket,
                    'seat' => $item->seat,
                    'qrSvg' => $qrSvg,
                ];
            }
        }

        return view('customer.bookings.confirmation', compact('booking', 'ticketsWithQr'));
    }

    public function myBookings(Request $request)
    {
        $user = Auth::user();

        $bookings = Booking::with([
            'showtime.movie',
            'showtime.screen.cinema',
            'items.seat',
            'items.ticket',
            'latestPayment',
        ])
        ->where('user_id', $user?->id)
        ->orWhere('customer_email', $user?->email)
        ->orderByDesc('created_at')
        ->paginate(10);

        return view('customer.bookings.my-bookings', compact('bookings'));
    }

    public function cancel(Booking $booking, Request $request, BookingService $bookingService)
    {
        $user = Auth::user();

        // Check ownership or admin
        if ($user && ($booking->user_id === $user->id || $user->isAdmin())) {
            $reason = $request->input('reason', 'Customer requested cancellation via portal');
            $bookingService->cancelBooking($booking, $reason, $user);

            return redirect()->back()->with('success', 'Booking cancelled successfully. Associated seats have been released and refund recorded.');
        }

        return redirect()->back()->with('error', 'Unauthorized to cancel this booking.');
    }
}

