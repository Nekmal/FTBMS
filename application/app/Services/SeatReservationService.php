<?php

namespace App\Services;

use App\Models\SeatHold;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeatUnavailableException extends Exception {}

class SeatReservationService
{
    public const HOLD_DURATION_MINUTES = 7;

    /**
     * Atomically lock requested seats, clean up expired holds, and create a 7-minute hold.
     *
     * @param int $showtimeId
     * @param array $requestedSeatIds
     * @param User|null $user
     * @param string|null $sessionToken
     * @return SeatHold
     * @throws SeatUnavailableException
     */
    public function createHold(int $showtimeId, array $requestedSeatIds, ?User $user = null, ?string $sessionToken = null): SeatHold
    {
        if (empty($requestedSeatIds)) {
            throw new SeatUnavailableException('No seats selected.');
        }

        $sessionToken = $sessionToken ?: Str::random(40);

        return DB::transaction(function () use ($showtimeId, $requestedSeatIds, $user, $sessionToken) {
            $seatIds = collect($requestedSeatIds)->unique()->sort()->values();

            // Lock inventory rows ordered by seat_id to prevent deadlocks
            $seats = ShowSeat::query()
                ->where('showtime_id', $showtimeId)
                ->whereIn('seat_id', $seatIds)
                ->orderBy('seat_id')
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== $seatIds->count()) {
                throw new SeatUnavailableException('One or more selected seats do not exist in this showtime.');
            }

            // Evaluate availability: release expired holds on the fly
            $now = Carbon::now();
            foreach ($seats as $seat) {
                if ($seat->status === 'SOLD' || $seat->status === 'BLOCKED') {
                    throw new SeatUnavailableException("Seat is not available ({$seat->status}).");
                }

                if ($seat->status === 'HELD') {
                    // Check if hold has expired
                    if ($seat->hold_expires_at && $seat->hold_expires_at->lessThanOrEqualTo($now)) {
                        // Release expired seat
                        $seat->status = 'AVAILABLE';
                        $seat->seat_hold_id = null;
                        $seat->hold_expires_at = null;
                        $seat->save();
                    } else {
                        // If it's held by the same user/session and still active, that's allowed; otherwise conflict
                        $existingHold = $seat->seatHold;
                        $isSameOwner = ($user && $existingHold && $existingHold->user_id === $user->id) ||
                                       ($sessionToken && $existingHold && $existingHold->session_token === $sessionToken);

                        if (!$isSameOwner) {
                            throw new SeatUnavailableException('One or more selected seats are currently reserved by another customer.');
                        }
                    }
                }
            }

            $expiresAt = $now->copy()->addMinutes(self::HOLD_DURATION_MINUTES);

            // Create hold record
            $hold = SeatHold::create([
                'user_id' => $user?->id,
                'session_token' => $sessionToken,
                'showtime_id' => $showtimeId,
                'status' => 'ACTIVE',
                'expires_at' => $expiresAt,
            ]);

            // Assign seats to the new hold
            foreach ($seats as $seat) {
                $seat->update([
                    'status' => 'HELD',
                    'seat_hold_id' => $hold->id,
                    'hold_expires_at' => $expiresAt,
                ]);
            }

            return $hold;
        }, 5);
    }

    /**
     * Release a specific seat hold.
     */
    public function releaseHold(SeatHold $hold): void
    {
        DB::transaction(function () use ($hold) {
            ShowSeat::query()
                ->where('seat_hold_id', $hold->id)
                ->where('status', 'HELD')
                ->update([
                    'status' => 'AVAILABLE',
                    'seat_hold_id' => null,
                    'hold_expires_at' => null,
                ]);

            $hold->update(['status' => 'RELEASED']);
        });
    }

    /**
     * Release all expired holds in the database.
     */
    public function releaseExpiredHolds(): int
    {
        $now = Carbon::now();

        $expiredHolds = SeatHold::query()
            ->where('status', 'ACTIVE')
            ->where('expires_at', '<=', $now)
            ->get();

        $count = 0;
        foreach ($expiredHolds as $hold) {
            $this->releaseHold($hold);
            $hold->update(['status' => 'EXPIRED']);
            $count++;
        }

        return $count;
    }

    /**
     * Get seat inventory for a showtime, releasing expired holds in real-time.
     */
    public function getShowtimeSeatMap(int $showtimeId): Collection
    {
        $now = Carbon::now();

        // Release expired holds for this showtime
        $expiredSeats = ShowSeat::query()
            ->where('showtime_id', $showtimeId)
            ->where('status', 'HELD')
            ->where('hold_expires_at', '<=', $now)
            ->get();

        foreach ($expiredSeats as $es) {
            $es->update([
                'status' => 'AVAILABLE',
                'seat_hold_id' => null,
                'hold_expires_at' => null,
            ]);
        }

        return ShowSeat::with('seat')
            ->where('showtime_id', $showtimeId)
            ->join('seats', 'show_seats.seat_id', '=', 'seats.id')
            ->orderBy('seats.row_label')
            ->orderBy('seats.seat_number')
            ->select('show_seats.*')
            ->get();
    }
}

