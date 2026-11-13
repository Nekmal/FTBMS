<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Customer\BookingConfirmationController;
use App\Http\Controllers\Customer\MovieCatalogController;
use App\Livewire\Customer\Checkout;
use App\Livewire\Customer\SeatSelection;
use App\Livewire\Staff\CounterBooking;
use App\Livewire\Staff\TicketScanner;
use Illuminate\Support\Facades\Route;

// Public Customer Movie Catalog
Route::get('/', [MovieCatalogController::class, 'index'])->name('home');
Route::get('/movies', [MovieCatalogController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie:slug}', [MovieCatalogController::class, 'show'])->name('movies.show');

// Interactive Seat Selection (Livewire)
Route::get('/showtimes/{showtime}/seats', SeatSelection::class)->name('showtimes.seats');

// Checkout & Mock Online Gateway (Livewire)
Route::get('/checkout/{hold}', Checkout::class)->name('checkout.show');

// Booking Confirmation & Tickets
Route::get('/bookings/{booking}/confirmation', [BookingConfirmationController::class, 'show'])->name('bookings.confirmation');

// Authenticated Customer Bookings
Route::middleware(['auth'])->group(function () {
    Route::get('/my-bookings', [BookingConfirmationController::class, 'myBookings'])->name('my-bookings');
    Route::post('/bookings/{booking}/cancel', [BookingConfirmationController::class, 'cancel'])->name('bookings.cancel');

    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
});

// Staff / Box Office POS & Scanner (Counter Staff, Cinema Managers, Admin)
Route::middleware(['auth', 'role:ADMIN,CINEMA_MANAGER,COUNTER_STAFF'])->prefix('staff')->group(function () {
    Route::get('/counter-sales', CounterBooking::class)->name('staff.counter-sales');
    Route::get('/ticket-scanner', TicketScanner::class)->name('staff.ticket-scanner');
});

// Admin & Branch Manager Reports & Analytics
Route::middleware(['auth', 'role:ADMIN,CINEMA_MANAGER'])->prefix('admin')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
});

require __DIR__.'/auth.php';
