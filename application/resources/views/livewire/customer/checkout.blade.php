<div class="py-10 bg-slate-900 min-h-screen text-slate-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 border-b border-slate-800 pb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight">Checkout & Payment</h1>
                <p class="text-sm text-slate-400 mt-1">Review your booking summary and complete payment</p>
            </div>

            <!-- 7-Minute Hold Countdown Banner -->
            <div x-data="{
                seconds: {{ $secondsRemaining }},
                init() {
                    let timer = setInterval(() => {
                        if (this.seconds > 0) {
                            this.seconds--;
                        } else {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    }, 1000);
                },
                get formattedTime() {
                    let m = Math.floor(this.seconds / 60);
                    let s = this.seconds % 60;
                    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                }
            }" class="bg-amber-950/70 border border-amber-500/70 text-amber-300 px-4 py-2.5 rounded-xl flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-400 shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <div class="text-xs uppercase font-bold tracking-wider text-amber-400">Seats Reserved For</div>
                    <div class="text-lg font-black text-white font-mono" x-text="formattedTime">--:--</div>
                </div>
            </div>
        </div>

        @if ($errorMessage)
            <div class="mb-6 bg-rose-950/80 border border-rose-500 text-rose-200 px-5 py-4 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $errorMessage }}</span>
                </div>
                <button wire:click="$set('errorMessage', null)" class="text-rose-400 hover:text-white">&times;</button>
            </div>
        @endif

        @if ($successMessage)
            <div class="mb-6 bg-emerald-950/80 border border-emerald-500 text-emerald-200 px-5 py-4 rounded-xl flex items-center gap-3">
                <svg class="w-6 h-6 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $successMessage }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Customer Form & Payment Simulation -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Customer Details Card -->
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Customer Information
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Full Name *</label>
                            <input
                                type="text"
                                wire:model="customerName"
                                placeholder="e.g. Kasun Silva"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm"
                            />
                            @error('customerName') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email Address (for QR Tickets) *</label>
                                <input
                                    type="email"
                                    wire:model="customerEmail"
                                    placeholder="kasun@example.com"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm"
                                />
                                @error('customerEmail') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Mobile Phone (Sri Lanka)</label>
                                <input
                                    type="tel"
                                    wire:model="customerPhone"
                                    placeholder="+94 77 123 4567"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-sm"
                                />
                                @error('customerPhone') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Card (Simulated Online Gateway) -->
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Online Payment Gateway (Demonstration)
                    </h2>
                    <p class="text-xs text-slate-400 mb-4">
                        For local presentation, a full payment workflow simulation is enabled. No real money or card numbers are processed.
                    </p>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-emerald-600/50 bg-emerald-950/20 cursor-pointer">
                            <input
                                type="radio"
                                wire:model="simulationOutcome"
                                value="SUCCESS"
                                class="text-emerald-500 focus:ring-emerald-500 bg-slate-900 border-slate-700"
                            />
                            <div>
                                <div class="text-sm font-bold text-white">Simulate Successful Payment (Instant Approval)</div>
                                <div class="text-xs text-slate-400">Verifies transaction, confirms booking, and generates admission QR tickets.</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-rose-800/50 bg-rose-950/20 cursor-pointer">
                            <input
                                type="radio"
                                wire:model="simulationOutcome"
                                value="FAILURE"
                                class="text-rose-500 focus:ring-rose-500 bg-slate-900 border-slate-700"
                            />
                            <div>
                                <div class="text-sm font-bold text-white">Simulate Payment Failure / Declined</div>
                                <div class="text-xs text-slate-400">Simulates card decline or timeout, maintaining seat hold for retry.</div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Right Column: Order Summary & Promo Code -->
            <div class="lg:col-span-5 space-y-6">
                
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-3">Booking Summary</h2>

                    <!-- Movie & Showtime Info -->
                    <div class="mb-5">
                        <h3 class="font-extrabold text-white text-base">{{ $hold->showtime->movie->title }}</h3>
                        <p class="text-xs text-emerald-400 font-semibold mt-0.5">{{ $hold->showtime->screen->cinema->name }}</p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $hold->showtime->screen->name }} &bull;
                            {{ $hold->showtime->starts_at->format('D, d M Y @ h:i A') }}
                        </p>
                    </div>

                    <!-- Seats Breakdown -->
                    <div class="space-y-2 mb-5 border-y border-slate-800/80 py-4 text-xs">
                        @foreach ($this->heldSeats as $showSeat)
                            @php
                                $price = $hold->showtime->getPriceForSeatType($showSeat->seat->seat_type);
                            @endphp
                            <div class="flex items-center justify-between text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold bg-slate-800 px-2 py-0.5 rounded text-white border border-slate-700">
                                        {{ $showSeat->seat->seat_code }}
                                    </span>
                                    <span>Seat {{ $showSeat->seat->seat_number }} ({{ $showSeat->seat->seat_type }})</span>
                                </div>
                                <span class="font-semibold text-slate-200">
                                    Rs. {{ number_format($price / 100, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Promo Code Box -->
                    <div class="mb-5">
                        @if ($appliedPromoCode)
                            <div class="flex items-center justify-between bg-emerald-950/40 border border-emerald-500/50 p-2.5 rounded-xl text-xs">
                                <div>
                                    <span class="font-bold text-emerald-400">{{ $appliedPromoCode }}</span> applied!
                                </div>
                                <button type="button" wire:click="removePromotion" class="text-rose-400 hover:text-rose-300 font-bold ml-2">Remove</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    wire:model="promoCode"
                                    placeholder="Promo code (e.g. BMS10)"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white uppercase focus:outline-none focus:border-emerald-500"
                                />
                                <button
                                    type="button"
                                    wire:click="applyPromotion"
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-600 transition"
                                >
                                    Apply
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Totals Table -->
                    <div class="space-y-2 text-xs border-b border-slate-800 pb-4 mb-5">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal ({{ count($this->heldSeats) }} seats):</span>
                            <span>Rs. {{ number_format($subtotalMinor / 100, 2) }}</span>
                        </div>

                        @if ($discountMinor > 0)
                            <div class="flex justify-between text-emerald-400 font-semibold">
                                <span>Discount:</span>
                                <span>- Rs. {{ number_format($discountMinor / 100, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-base font-black text-white pt-2 border-t border-slate-800">
                            <span>Total (LKR):</span>
                            <span class="text-emerald-400">Rs. {{ number_format($finalTotalMinor / 100, 2) }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button
                            type="button"
                            wire:click="submitPayment"
                            wire:loading.attr="disabled"
                            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-slate-950 font-black rounded-xl shadow-lg shadow-emerald-500/20 text-sm tracking-wide transition flex items-center justify-center gap-2"
                        >
                            <span wire:loading.remove>
                                Pay & Confirm &bull; Rs. {{ number_format($finalTotalMinor / 100, 2) }}
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Processing Authorization...
                            </span>
                        </button>

                        <button
                            type="button"
                            wire:click="cancelReservation"
                            class="w-full py-2.5 bg-transparent hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition"
                        >
                            Cancel Reservation & Choose Different Seats
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

