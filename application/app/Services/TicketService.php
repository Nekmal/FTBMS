<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Ticket;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    /**
     * Issue tickets for each item in a confirmed booking.
     */
    public function issueTicketsForBooking(Booking $booking): Collection
    {
        $tickets = collect();

        foreach ($booking->items as $item) {
            // Check if ticket already exists
            $existing = Ticket::where('booking_item_id', $item->id)->first();
            if ($existing) {
                $tickets->push($existing);
                continue;
            }

            $ticketCode = 'TKT-' . strtoupper(Str::random(10));
            $qrTokenHash = hash('sha256', $ticketCode . '-' . Str::random(20));

            $ticket = Ticket::create([
                'booking_item_id' => $item->id,
                'ticket_code' => $ticketCode,
                'qr_token_hash' => $qrTokenHash,
                'status' => 'ISSUED',
            ]);

            $tickets->push($ticket);
        }

        return $tickets;
    }

    /**
     * Generate inline SVG QR code data URL.
     */
    public function generateQrCodeSvg(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
            'addQuietzone' => true,
            'svgUseFill' => true,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Atomically validate a ticket by QR token hash or ticket code.
     *
     * @param string $tokenOrCode
     * @param User $staffUser
     * @return array
     */
    public function validateTicket(string $tokenOrCode, User $staffUser): array
    {
        $tokenOrCode = trim($tokenOrCode);

        return DB::transaction(function () use ($tokenOrCode, $staffUser) {
            $ticket = Ticket::query()
                ->where('qr_token_hash', $tokenOrCode)
                ->orWhere('ticket_code', $tokenOrCode)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return [
                    'valid' => false,
                    'message' => 'Invalid ticket code or QR token. Ticket not found.',
                ];
            }

            // Check if staff has permission for this cinema
            $booking = $ticket->bookingItem->booking;
            $showtime = $booking->showtime;
            $cinema = $showtime->screen->cinema;

            if (!$staffUser->isAdmin() && !$staffUser->isCounterStaff($cinema->id)) {
                return [
                    'valid' => false,
                    'message' => "Access denied. You are not assigned to {$cinema->name}.",
                ];
            }

            if ($ticket->status === 'VALIDATED') {
                return [
                    'valid' => false,
                    'status' => 'ALREADY_USED',
                    'message' => "Duplicate entry! This ticket was already validated at {$ticket->validated_at?->format('h:i A, d M Y')}.",
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'CANCELLED') {
                return [
                    'valid' => false,
                    'status' => 'CANCELLED',
                    'message' => 'This ticket has been cancelled or refunded.',
                    'ticket' => $ticket,
                ];
            }

            // Mark as validated atomically
            $ticket->update([
                'status' => 'VALIDATED',
                'validated_at' => now(),
                'validated_by_user_id' => $staffUser->id,
            ]);

            return [
                'valid' => true,
                'status' => 'SUCCESS',
                'message' => 'Ticket valid! Admitted successfully.',
                'ticket' => $ticket->load(['bookingItem.seat', 'bookingItem.booking.showtime.movie', 'bookingItem.booking.showtime.screen.cinema']),
            ];
        }, 5);
    }
}
