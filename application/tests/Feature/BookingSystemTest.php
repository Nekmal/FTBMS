<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserRole;
use App\Services\BookingService;
use App\Services\CashPaymentService;
use App\Services\PaymentService;
use App\Services\SeatReservationService;
use App\Services\SeatUnavailableException;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seed database for testing
        $this->seed();
    }

    public function test_customer_can_browse_movies_and_view_catalog()
    {
        $response = $this->get('/movies');
        $response->assertStatus(200);
        $response->assertSee('Now Showing');
        $response->assertSee('Dune: Part Two');
    }

    public function test_customer_can_hold_seats_successfully()
    {
        $showtime = Showtime::first();
        $seats = Seat::where('layout_id', $showtime->layout_id)->take(2)->get();
        $seatIds = $seats->pluck('id')->toArray();

        $service = app(SeatReservationService::class);
        $customer = User::where('email', 'customer@bms.lk')->first();

        $hold = $service->createHold($showtime->id, $seatIds, $customer);

        $this->assertInstanceOf(SeatHold::class, $hold);
        $this->assertEquals('ACTIVE', $hold->status);
        $this->assertTrue($hold->expires_at->isFuture());

        // Check show_seats updated to HELD
        $heldSeats = ShowSeat::where('showtime_id', $showtime->id)
            ->whereIn('seat_id', $seatIds)
            ->get();

        foreach ($heldSeats as $hs) {
            $this->assertEquals('HELD', $hs->status);
            $this->assertEquals($hold->id, $hs->seat_hold_id);
        }
    }

    public function test_concurrent_reservation_conflict_prevents_duplicate_holds()
    {
        $showtime = Showtime::first();
        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $seatIds = [$seat->id];

        $service = app(SeatReservationService::class);
        $customer1 = User::where('email', 'customer@bms.lk')->first();
        $admin = User::where('email', 'admin@bms.lk')->first();

        // Customer 1 holds the seat
        $hold1 = $service->createHold($showtime->id, $seatIds, $customer1);
        $this->assertNotNull($hold1);

        // Customer 2 attempts to hold the same seat simultaneously
        $this->expectException(SeatUnavailableException::class);
        $service->createHold($showtime->id, $seatIds, $admin);
    }

    public function test_expired_holds_are_safely_released()
    {
        $showtime = Showtime::first();
        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $seatIds = [$seat->id];

        $service = app(SeatReservationService::class);
        $customer = User::where('email', 'customer@bms.lk')->first();

        // Create hold
        $hold = $service->createHold($showtime->id, $seatIds, $customer);

        // Artificially expire the hold
        $hold->update(['expires_at' => Carbon::now()->subMinutes(10)]);
        ShowSeat::where('seat_hold_id', $hold->id)->update([
            'hold_expires_at' => Carbon::now()->subMinutes(10),
        ]);

        // Release expired holds
        $released = $service->releaseExpiredHolds();
        $this->assertGreaterThanOrEqual(1, $released);

        // Seat should be AVAILABLE again
        $showSeat = ShowSeat::where('showtime_id', $showtime->id)->where('seat_id', $seat->id)->first();
        $this->assertEquals('AVAILABLE', $showSeat->status);
    }

    public function test_online_payment_success_confirms_booking_and_issues_qr_tickets()
    {
        $showtime = Showtime::first();
        $seats = Seat::where('layout_id', $showtime->layout_id)->take(2)->get();
        $seatIds = $seats->pluck('id')->toArray();

        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);

        $customer = User::where('email', 'customer@bms.lk')->first();
        $hold = $reservationService->createHold($showtime->id, $seatIds, $customer);

        // 1. Create Pending Booking
        $booking = $bookingService->createPendingBookingFromHold(
            $hold,
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            'ONLINE'
        );

        $this->assertEquals('PENDING', $booking->status);
        $this->assertGreaterThan(0, $booking->total_minor);

        // 2. Process Online Mock Payment (SUCCESS)
        $payment = $paymentService->processOnlineMockPayment($booking, 'SUCCESS');

        $this->assertEquals('SUCCESS', $payment->status);
        $booking->refresh();
        $this->assertEquals('CONFIRMED', $booking->status);

        // Check seats transitioned to SOLD
        $soldSeats = ShowSeat::where('booking_id', $booking->id)->get();
        $this->assertCount(2, $soldSeats);
        foreach ($soldSeats as $ss) {
            $this->assertEquals('SOLD', $ss->status);
        }

        // Check QR tickets issued
        $tickets = Ticket::whereHas('bookingItem', fn($q) => $q->where('booking_id', $booking->id))->get();
        $this->assertCount(2, $tickets);
        foreach ($tickets as $tkt) {
            $this->assertEquals('ISSUED', $tkt->status);
            $this->assertNotEmpty($tkt->qr_token_hash);
            $this->assertStringStartsWith('TKT-', $tkt->ticket_code);
        }
    }

    public function test_online_payment_failure_does_not_issue_tickets()
    {
        $showtime = Showtime::first();
        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $seatIds = [$seat->id];

        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);

        $customer = User::where('email', 'customer@bms.lk')->first();
        $hold = $reservationService->createHold($showtime->id, $seatIds, $customer);

        $booking = $bookingService->createPendingBookingFromHold(
            $hold,
            ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
            'ONLINE'
        );

        // Process Online Mock Payment (FAILURE)
        $payment = $paymentService->processOnlineMockPayment($booking, 'FAILURE');

        $this->assertEquals('FAILED', $payment->status);
        $booking->refresh();
        $this->assertEquals('PENDING', $booking->status);

        // No tickets issued
        $tickets = Ticket::whereHas('bookingItem', fn($q) => $q->where('booking_id', $booking->id))->get();
        $this->assertCount(0, $tickets);
    }

    public function test_counter_staff_can_record_cash_payment_and_calculate_change()
    {
        $showtime = Showtime::first();
        $cinemaId = $showtime->screen->cinema_id;

        $staff = User::whereHas('userRoles', fn($q) => $q->where('role', 'COUNTER_STAFF')->where('cinema_id', $cinemaId))->first();
        $this->assertNotNull($staff);

        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $seatIds = [$seat->id];

        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $cashPaymentService = app(CashPaymentService::class);

        $hold = $reservationService->createHold($showtime->id, $seatIds, $staff);
        $booking = $bookingService->createPendingBookingFromHold(
            $hold,
            ['name' => 'Walkin Guest', 'email' => 'counter@bms.lk'],
            'COUNTER',
            $staff
        );

        // Total price for Standard seat is 100,000 cents (Rs. 1,000.00). Customer pays Rs. 2,000.00 (200,000 cents)
        $cashReceivedMinor = 200000;
        $payment = $cashPaymentService->recordCashPayment($booking, $staff, $cashReceivedMinor);

        $this->assertEquals('SUCCESS', $payment->status);
        $this->assertEquals('CASH', $payment->method);
        $this->assertEquals(100000, $payment->cash_returned_minor); // Rs. 1,000.00 change
        $this->assertEquals($staff->id, $payment->staff_user_id);

        $booking->refresh();
        $this->assertEquals('CONFIRMED', $booking->status);
    }

    public function test_counter_staff_cannot_record_sale_for_unassigned_branch()
    {
        $showtime = Showtime::first();
        $cinemaId = $showtime->screen->cinema_id;

        // Staff assigned to a different cinema
        $otherStaff = User::whereHas('userRoles', fn($q) => $q->where('role', 'COUNTER_STAFF')->where('cinema_id', '!=', $cinemaId))->first();

        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $seatIds = [$seat->id];

        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $cashPaymentService = app(CashPaymentService::class);

        $hold = $reservationService->createHold($showtime->id, $seatIds, $otherStaff);
        $booking = $bookingService->createPendingBookingFromHold(
            $hold,
            ['name' => 'Guest', 'email' => 'guest@bms.lk'],
            'COUNTER',
            $otherStaff
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized: You are not assigned as counter staff to this cinema branch.');
        $cashPaymentService->recordCashPayment($booking, $otherStaff, 200000);
    }

    public function test_ticket_validation_and_duplicate_scan_prevention()
    {
        $showtime = Showtime::first();
        $cinemaId = $showtime->screen->cinema_id;
        $staff = User::whereHas('userRoles', fn($q) => $q->where('role', 'COUNTER_STAFF')->where('cinema_id', $cinemaId))->first();

        // Create confirmed booking with ticket
        $seat = Seat::where('layout_id', $showtime->layout_id)->first();
        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);
        $ticketService = app(TicketService::class);

        $hold = $reservationService->createHold($showtime->id, [$seat->id]);
        $booking = $bookingService->createPendingBookingFromHold($hold, ['name' => 'Scanner Test', 'email' => 'test@test.com']);
        $payment = $paymentService->processOnlineMockPayment($booking, 'SUCCESS');

        $ticket = Ticket::whereHas('bookingItem', fn($q) => $q->where('booking_id', $booking->id))->first();
        $this->assertEquals('ISSUED', $ticket->status);

        // First Scan -> SUCCESS
        $scan1 = $ticketService->validateTicket($ticket->ticket_code, $staff);
        $this->assertTrue($scan1['valid']);
        $this->assertEquals('SUCCESS', $scan1['status']);

        // Second Scan with SAME ticket -> DUPLICATE REJECTED
        $scan2 = $ticketService->validateTicket($ticket->ticket_code, $staff);
        $this->assertFalse($scan2['valid']);
        $this->assertEquals('ALREADY_USED', $scan2['status']);
    }

    public function test_booking_cancellation_releases_seats_and_records_refund()
    {
        $showtime = Showtime::first();
        $seat = Seat::where('layout_id', $showtime->layout_id)->first();

        $reservationService = app(SeatReservationService::class);
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);

        $customer = User::where('email', 'customer@bms.lk')->first();
        $hold = $reservationService->createHold($showtime->id, [$seat->id], $customer);
        $booking = $bookingService->createPendingBookingFromHold($hold, ['name' => 'Cancel Test', 'email' => 'cancel@test.com']);
        $payment = $paymentService->processOnlineMockPayment($booking, 'SUCCESS');

        // Cancel booking
        $result = $bookingService->cancelBooking($booking, 'Customer requested cancellation', $customer);
        $this->assertTrue($result);

        $booking->refresh();
        $this->assertEquals('CANCELLED', $booking->status);

        // Seat is available again
        $showSeat = ShowSeat::where('showtime_id', $showtime->id)->where('seat_id', $seat->id)->first();
        $this->assertEquals('AVAILABLE', $showSeat->status);

        // Refund recorded
        $refund = $booking->refunds()->first();
        $this->assertNotNull($refund);
        $this->assertEquals('COMPLETED', $refund->status);
        $this->assertEquals($booking->total_minor, $refund->amount_minor);
    }

    public function test_admin_sales_reporting_reconciles_amounts()
    {
        $admin = User::where('email', 'admin@bms.lk')->first();

        $response = $this->actingAs($admin)->get('/admin/reports');
        $response->assertStatus(200);
        $response->assertSee('Sales & Occupancy Reports');
        $response->assertSee('Net Revenue (LKR)');
    }
}

