<div wire:poll.4s class="py-8 bg-slate-900 min-h-screen text-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <a href="{{ route('movies.show', $showtime->movie->slug) }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 flex items-center gap-1 mb-1">
                    &larr; Back to Movie Details
                </a>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                    {{ $showtime->movie->title }}
                    <span class="text-xs bg-slate-800 border border-slate-700 text-slate-300 px-2 py-0.5 rounded-full font-medium">
                        {{ $showtime->movie->rating }}
                    </span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    <span class="text-emerald-400 font-semibold">{{ $showtime->screen->cinema->name }}</span> &bull;
                    {{ $showtime->screen->name }} &bull;
                    {{ $showtime->starts_at->format('l, d M Y - h:i A') }}
                </p>
            </div>

            <!-- Price Legend -->
            <div class="flex flex-wrap items-center gap-3 text-xs bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                <div class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 rounded bg-emerald-500/20 border border-emerald-500 inline-block"></span>
                    <span>Standard <strong class="text-emerald-400">Rs. 1,000</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 rounded bg-purple-500/20 border border-purple-500 inline-block"></span>
                    <span>Premium <strong class="text-purple-400">Rs. 1,500</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 rounded bg-amber-500/20 border border-amber-500 inline-block"></span>
                    <span>VIP <strong class="text-amber-400">Rs. 2,000</strong></span>
                </div>
            </div>
        </div>

        @if ($errorMessage)
            <div class="mb-6 bg-rose-950/80 border border-rose-500 text-rose-200 px-4 py-3 rounded-xl flex items-center justify-between animate-pulse">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ $errorMessage }}</span>
                </div>
                <button wire:click="$set('errorMessage', null)" class="text-rose-400 hover:text-white">&times;</button>
            </div>
        @endif

        <!-- Cinema Screen Curve -->
        <div class="my-8 text-center">
            <div class="w-3/4 mx-auto h-2 bg-gradient-to-r from-transparent via-cyan-400 to-transparent rounded-full shadow-[0_0_20px_rgba(34,211,238,0.5)]"></div>
            <p class="text-xs uppercase tracking-widest text-slate-500 mt-2 font-bold">ALL EYES THIS WAY &bull; SCREEN</p>
        </div>

        <!-- Interactive Seating Grid -->
        <div class="bg-slate-950/70 p-6 sm:p-8 rounded-2xl border border-slate-800 shadow-2xl overflow-x-auto">
            <div class="min-w-[650px] flex flex-col items-center gap-3">
                @foreach ($seatsGroupedByRow as $rowLabel => $rowSeats)
                    <div class="flex items-center gap-3 w-full justify-center">
                        <!-- Row Identifier Left -->
                        <span class="w-6 text-center font-bold text-slate-400 text-xs">{{ $rowLabel }}</span>

                        <div class="flex items-center gap-2">
                            @foreach ($rowSeats as $seat)
                                @php
                                    $inv = $inventory->get($seat->id);
                                    $status = $inv ? $inv->status : 'AVAILABLE';
                                    
                                    // Check if hold has expired
                                    if ($status === 'HELD' && $inv->hold_expires_at && $inv->hold_expires_at->isPast()) {
                                        $status = 'AVAILABLE';
                                    }

                                    $isSelected = in_array($seat->id, $selectedSeatIds);

                                    // Determine styling
                                    $colorClass = match(true) {
                                        $isSelected => 'bg-indigo-600 border-indigo-400 text-white shadow-lg shadow-indigo-600/50 scale-105',
                                        $status === 'SOLD' => 'bg-slate-800 border-slate-700 text-slate-600 cursor-not-allowed opacity-40',
                                        $status === 'HELD' => 'bg-amber-950/70 border-amber-600 text-amber-500 cursor-not-allowed animate-pulse',
                                        $status === 'BLOCKED' => 'bg-slate-800 border-red-900 text-red-500 cursor-not-allowed',
                                        $seat->seat_type === 'VIP' => 'bg-amber-950/30 border-amber-500/60 text-amber-300 hover:bg-amber-500 hover:text-slate-950',
                                        $seat->seat_type === 'PREMIUM' => 'bg-purple-950/30 border-purple-500/60 text-purple-300 hover:bg-purple-500 hover:text-white',
                                        default => 'bg-emerald-950/30 border-emerald-500/50 text-emerald-300 hover:bg-emerald-500 hover:text-slate-950',
                                    };
                                @endphp

                                <button
                                    type="button"
                                    wire:click="toggleSeat({{ $seat->id }})"
                                    @disabled($status === 'SOLD' || $status === 'HELD' || $status === 'BLOCKED')
                                    title="Seat {{ $seat->seat_code }} ({{ $seat->seat_type }}) - {{ $status }}"
                                    class="w-8 h-8 rounded-lg border text-xs font-semibold flex items-center justify-center transition-all duration-150 {{ $colorClass }}"
                                >
                                    {{ $seat->seat_number }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Row Identifier Right -->
                        <span class="w-6 text-center font-bold text-slate-400 text-xs">{{ $rowLabel }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Seat State Legend -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded bg-emerald-950/40 border border-emerald-500 inline-block"></span>
                    <span>Available</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded bg-indigo-600 border border-indigo-400 inline-block"></span>
                    <span>Selected</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded bg-amber-950/70 border border-amber-600 inline-block"></span>
                    <span>Reserved / Held</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded bg-slate-800 border border-slate-700 opacity-40 inline-block"></span>
                    <span>Sold</span>
                </div>
            </div>
        </div>

        <!-- Sticky Floating Checkout Bar -->
        <div class="mt-8 bg-slate-800/90 backdrop-blur-md border border-slate-700 rounded-2xl p-5 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-600/20 border border-indigo-500 flex items-center justify-center text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Selected Seats</span>
                    <div class="text-lg font-bold text-white">
                        @if (empty($selectedSeatIds))
                            <span class="text-slate-500 text-sm font-normal">No seats selected yet</span>
                        @else
                            {{ count($selectedSeatIds) }} seat(s)
                            <span class="text-xs font-normal text-slate-400 ml-1">
                                (Max 8 seats)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6 w-full sm:w-auto justify-between sm:justify-end">
                <div class="text-right">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Estimated Total</span>
                    <div class="text-2xl font-black text-emerald-400">
                        Rs. {{ number_format($totalMinor / 100, 2) }}
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="proceedToHold"
                    wire:loading.attr="disabled"
                    @disabled(empty($selectedSeatIds))
                    class="px-6 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-black rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2"
                >
                    <span wire:loading.remove>Reserve Seats &rarr;</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Securing Seats...
                    </span>
                </button>
            </div>
        </div>

    </div>
</div>

