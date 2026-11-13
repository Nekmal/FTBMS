<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ShowSeat;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Enforce branch permission: Manager only sees their own branch
        $cinemaId = $request->query('cinema_id');
        if (!$user->isAdmin()) {
            $assigned = $user->assignedCinema;
            $cinemaId = $assigned?->id;
        }

        $cinemas = $user->isAdmin() ? Cinema::all() : Cinema::where('id', $cinemaId)->get();

        // Payments Query
        $paymentsQuery = Payment::with(['booking.showtime.screen.cinema'])
            ->where('status', 'SUCCESS')
            ->when($cinemaId, function ($q) use ($cinemaId) {
                $q->whereHas('booking.showtime.screen', fn($sq) => $sq->where('cinema_id', $cinemaId));
            });

        $onlineSalesMinor = (clone $paymentsQuery)->where('method', 'ONLINE_MOCK')->sum('amount_minor');
        $cashSalesMinor = (clone $paymentsQuery)->where('method', 'CASH')->sum('amount_minor');
        $grossSalesMinor = $onlineSalesMinor + $cashSalesMinor;

        // Refunds Query
        $refundsQuery = Refund::with(['booking.showtime.screen.cinema'])
            ->where('status', 'COMPLETED')
            ->when($cinemaId, function ($q) use ($cinemaId) {
                $q->whereHas('booking.showtime.screen', fn($sq) => $sq->where('cinema_id', $cinemaId));
            });

        $totalRefundsMinor = $refundsQuery->sum('amount_minor');
        $netSalesMinor = max(0, $grossSalesMinor - $totalRefundsMinor);

        // Occupancy calculation per cinema
        $occupancyData = [];
        foreach ($cinemas as $cinema) {
            $totalInventory = ShowSeat::whereHas('showtime.screen', fn($q) => $q->where('cinema_id', $cinema->id))->count();
            $soldSeats = ShowSeat::whereHas('showtime.screen', fn($q) => $q->where('cinema_id', $cinema->id))
                ->where('status', 'SOLD')
                ->count();
            $rate = $totalInventory > 0 ? round(($soldSeats / $totalInventory) * 100, 1) : 0;

            $occupancyData[] = [
                'cinema' => $cinema,
                'totalInventory' => $totalInventory,
                'soldSeats' => $soldSeats,
                'occupancyRate' => $rate,
            ];
        }

        // Recent Audit Logs
        $auditLogs = AuditLog::with(['actor', 'cinema'])
            ->when($cinemaId, fn($q) => $q->where('cinema_id', $cinemaId))
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        // Recent Bookings
        $recentBookings = Booking::with(['showtime.movie', 'showtime.screen.cinema', 'latestPayment'])
            ->when($cinemaId, function ($q) use ($cinemaId) {
                $q->whereHas('showtime.screen', fn($sq) => $sq->where('cinema_id', $cinemaId));
            })
            ->latest()
            ->take(10)
            ->get();

        return view('admin.reports.index', compact(
            'cinemas',
            'cinemaId',
            'grossSalesMinor',
            'onlineSalesMinor',
            'cashSalesMinor',
            'totalRefundsMinor',
            'netSalesMinor',
            'occupancyData',
            'auditLogs',
            'recentBookings'
        ));
    }
}

