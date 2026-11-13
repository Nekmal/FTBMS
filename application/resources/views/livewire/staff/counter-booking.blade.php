<div class="py-8 bg-slate-900 min-h-screen text-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <span class="text-xs uppercase tracking-widest text-emerald-400 font-bold">POS Terminal</span>
                <h1 class="text-3xl font-black text-white">Box Office Counter Sales</h1>
                <p class="text-xs text-slate-400 mt-1">Direct walk-in ticketing with instant cash handling and ticket generation.</p>
            </div>

            <!-- Branch & Showtime Selector -->
            <div class="flex flex-wrap items-center gap-3">
                @if (auth()->user()->isAdmin())
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Cinema Branch</label>
                        <select wire:model.live="selectedCinemaId" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                            @foreach ($cinemas as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="bg-slate-800/80 border border-slate-700 px-3 py-1.5 rounded-xl text-xs">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Assigned Branch</span>
                        <strong class="text-emerald-400">{{ $cinemas->first()?->name }}</strong>
                    </div>
                @endif

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Today's Showtime</label>
                    <select wire:model.live="selectedShowtimeId" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                        <option value="">-- Select Showtime --</option>
                        @foreach ($todayShowtimes as $st)
                            <option value="{{ $st->id }}">
                                {{ $st->starts_at->format('h:i A') }} - {{ $st->movie->title }} ({{ $st->screen->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($errorMessage)
            <div class="mb-6 bg-rose-950/80 border border-rose-500 text-rose-200 px-4 py-3 rounded-xl flex items-center justify-between text-xs">
                <span>{{ $errorMessage }}</span>
                <button wire:click="$set('errorMessage', null)" class="text-rose-400 hover:text-white font-bold">&times;</button>
            </div>
        @endif

        @if (!$selectedShowtime)
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-12 text-center">
                <p class="text-slate-400 text-sm">Please select a scheduled showtime above to open the counter seat map.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left Column: Seat Map -->
                <div class="lg:col-span-8 bg-slate-950/70 border border-slate-800 rounded-2xl p-6 shadow-xl overflow-x-auto">
                    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>{{ $selectedShowtime->movie->title }}</span>
                            <span class="text-xs text-slate-400 font-normal">({{ $selectedShowtime->screen->name }})</span>
                        </h2>
                        <span class="text-xs text-emerald-400 font-mono font-bold">{{ $selectedShowtime->starts_at->format('h:i A, d M Y') }}</span>
                    </div>

                    <!-- Screen Arc -->
                    <div class="my-6 text-center">
                        <div class="w-2/3 mx-auto h-1.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent rounded-full shadow-[0_0_15px_rgba(34,211,238,0.5)]"></div>
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 mt-1 font-bold">CINEMA SCREEN</p>
                    </div>

                    <!-- Seating Grid -->
                    <div class="min-w-[500px] flex flex-col items-center gap-2.5 my-4">
                        @foreach ($seatsGroupedByRow as $rowLabel => $rowSeats)
                            <div class="flex items-center gap-2.5 justify-center">
                                <span class="w-5 text-center font-bold text-slate-500 text-[11px]">{{ $rowLabel }}</span>
                                <div class="flex items-center gap-1.5">
                                    @foreach ($rowSeats as $seat)
                                        @php
                                            $inv = $inventory->get($seat->id);
                                            $status = $inv ? $inv->status : 'AVAILABLE';
                                            if ($status === 'HELD' && $inv->hold_expires_at && $inv->hold_expires_at->isPast()) {
                                                $status = 'AVAILABLE';
                                            }
                                            $isSelected = in_array($seat->id, $selectedSeatIds);

                                            $colorClass = match(true) {
                                                $isSelected => 'bg-indigo-600 border-indigo-400 text-white shadow-md scale-105',
                                                $status === 'SOLD' => 'bg-slate-800 border-slate-700 text-slate-600 cursor-not-allowed opacity-40',
                                                $status === 'HELD' => 'bg-amber-950 border-amber-600 text-amber-400 cursor-not-allowed',
                                                $seat->seat_type === 'VIP' => 'bg-amber-950/30 border-amber-500/60 text-amber-300 hover:bg-amber-500 hover:text-slate-950',
                                                default => 'bg-emerald-950/30 border-emerald-500/50 text-emerald-300 hover:bg-emerald-500 hover:text-slate-950',
                                            };
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="toggleSeat({{ $seat->id }})"
                                            @disabled($status === 'SOLD' || ($status === 'HELD' && !$isSelected))
                                            class="w-7 h-7 rounded border text-[10px] font-bold flex items-center justify-center transition {{ $colorClass }}"
                                        >
                                            {{ $seat->seat_number }}
                                        </button>
                                    @endforeach
                                </div>
                                <span class="w-5 text-center font-bold text-slate-500 text-[11px]">{{ $rowLabel }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Selected: <strong class="text-white">{{ count($selectedSeatIds) }} seat(s)</strong></span>
                        @if (!$activeHold)
                            <button
                                type="button"
                                wire:click="holdSeats"
                                @disabled(empty($selectedSeatIds))
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white font-bold rounded-xl text-xs transition"
                            >
                                Lock Seats for Cash Payment &rarr;
                            </button>
                        @else
                            <span class="text-emerald-400 font-bold">&#10003; Seats Held for Cashier</span>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Cash Register Drawer -->
                <div class="lg:col-span-4 bg-slate-950/80 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white mb-4 border-b border-slate-800 pb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Cash Collection Drawer
                        </h2>

                        <!-- Walk-in Customer Inputs -->
                        <div class="space-y-3 mb-6">
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Customer Name</label>
                                <input type="text" wire:model="customerName" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white" />
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Customer Mobile Phone</label>
                                <input type="text" wire:model="customerPhone" placeholder="+94 7X XXX XXXX" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white" />
                            </div>
                        </div>

                        <!-- Amount Calculation -->
                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3 mb-6">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400">Total Ticket Price:</span>
                                <span class="text-lg font-black text-white font-mono">
                                    Rs. {{ number_format($totalMinor / 100, 2) }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Cash Received (LKR) *</label>
                                <input
                                    type="number"
                                    step="100"
                                    wire:model.live="cashReceivedInput"
                                    class="w-full bg-slate-950 border border-emerald-500 rounded-xl px-3 py-2 text-base font-black text-emerald-400 text-right font-mono"
                                />
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                                <span class="text-xs text-slate-400 font-bold uppercase">Change to Return:</span>
                                <span class="text-lg font-black text-emerald-400 font-mono">
                                    Rs. {{ number_format($changeMinor / 100, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Cash Sale Button -->
                    <button
                        type="button"
                        wire:click="confirmCashSale"
                        @disabled(!$activeHold || (floatval($cashReceivedInput) * 100 < $totalMinor))
                        class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 disabled:opacity-40 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider transition shadow-lg shadow-emerald-500/20"
                    >
                        Confirm Cash & Print E-Tickets &rarr;
                    </button>
                </div>

            </div>
        @endif

    </div>
</div>

