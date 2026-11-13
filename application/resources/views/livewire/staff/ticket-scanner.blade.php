<div class="py-10 bg-slate-900 min-h-screen text-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 border-b border-slate-800 pb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs uppercase tracking-widest text-emerald-400 font-bold">Gate Admission Control</span>
                <h1 class="text-3xl font-black text-white">QR Ticket Validator & Scanner</h1>
                <p class="text-xs text-slate-400 mt-1">Scan visitor admission QR codes atomically to grant entry and prevent duplicate passes.</p>
            </div>

            <div class="bg-slate-800 border border-slate-700 px-4 py-2 rounded-xl text-xs">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Staff On Duty</span>
                <strong class="text-white">{{ auth()->user()->name }}</strong>
            </div>
        </div>

        @if ($errorMessage)
            <div class="mb-6 bg-rose-950/80 border border-rose-500 text-rose-200 px-5 py-3 rounded-xl text-xs flex items-center justify-between">
                <span>{{ $errorMessage }}</span>
                <button wire:click="$set('errorMessage', null)" class="text-rose-400 hover:text-white font-bold">&times;</button>
            </div>
        @endif

        <!-- Scanner Card -->
        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl mb-8">
            <form wire:submit.prevent="validateCode" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Scan QR Token / Enter Ticket Code
                    </label>
                    <div class="flex gap-3">
                        <input
                            type="text"
                            wire:model="scanInput"
                            placeholder="e.g. TKT-ABC123XYZ or paste full QR hash"
                            autofocus
                            class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-base text-white font-mono placeholder-slate-600 focus:outline-none focus:border-emerald-500"
                        />
                        <button
                            type="submit"
                            class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black rounded-xl text-sm transition shadow-lg shadow-emerald-500/20"
                        >
                            Validate Ticket &rarr;
                        </button>
                    </div>
                </div>
            </form>

            <!-- Scan Result Display -->
            @if ($scanResult)
                <div class="mt-6 pt-6 border-t border-slate-800">
                    @if ($scanResult['valid'])
                        <!-- Successful Admission -->
                        <div class="bg-emerald-950/60 border border-emerald-500 rounded-2xl p-6 shadow-xl animate-bounce-once">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-3xl shrink-0">
                                        &#10003;
                                    </div>
                                    <div>
                                        <span class="text-xs uppercase font-bold tracking-wider text-emerald-400">Gate Check Passed</span>
                                        <h3 class="text-2xl font-black text-white">ADMITTED &bull; VALID TICKET</h3>
                                        <p class="text-xs text-emerald-300 mt-0.5">{{ $scanResult['message'] }}</p>
                                    </div>
                                </div>
                                <span class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-mono font-bold px-3 py-1 rounded-full">
                                    {{ $scanResult['ticket']->ticket_code }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-4 border-t border-emerald-900/60 text-xs">
                                <div>
                                    <span class="text-slate-400 block">Movie</span>
                                    <strong class="text-white text-sm">{{ $scanResult['ticket']->bookingItem->booking->showtime->movie->title }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Seat Code</span>
                                    <strong class="text-emerald-400 text-xl font-mono">{{ $scanResult['ticket']->bookingItem->seat->seat_code }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Screen</span>
                                    <strong class="text-white">{{ $scanResult['ticket']->bookingItem->booking->showtime->screen->name }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Customer</span>
                                    <strong class="text-white">{{ $scanResult['ticket']->bookingItem->booking->customer_name }}</strong>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Duplicate or Invalid Ticket -->
                        <div class="bg-rose-950/80 border border-rose-500 rounded-2xl p-6 shadow-xl">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-rose-500 text-slate-950 flex items-center justify-center font-black text-3xl shrink-0">
                                    &#10005;
                                </div>
                                <div>
                                    <span class="text-xs uppercase font-bold tracking-wider text-rose-400">Admission Rejected</span>
                                    <h3 class="text-2xl font-black text-white">
                                        {{ $scanResult['status'] === 'ALREADY_USED' ? 'DUPLICATE ENTRY DETECTED!' : 'ENTRY DENIED' }}
                                    </h3>
                                    <p class="text-sm text-rose-200 mt-1 font-semibold">{{ $scanResult['message'] }}</p>
                                    @if (isset($scanResult['ticket']))
                                        <div class="mt-3 text-xs text-rose-300 font-mono">
                                            Ticket Code: {{ $scanResult['ticket']->ticket_code }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Demonstration Quick-Test Helper -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-3 flex items-center justify-between">
                <span>Demo Helper: Recently Issued Tickets for Rapid Testing</span>
                <span class="text-[10px] text-slate-500 font-normal">Click to test scanner & duplicate detection</span>
            </h3>

            @if ($recentTickets->isEmpty())
                <p class="text-xs text-slate-500 italic">No tickets have been issued yet. Book tickets online or via POS counter to test scanning.</p>
            @else
                <div class="space-y-2">
                    @foreach ($recentTickets as $rt)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs gap-2">
                            <div>
                                <span class="font-mono font-bold text-emerald-400">{{ $rt->ticket_code }}</span> &bull;
                                <span class="text-white font-semibold">{{ $rt->bookingItem->booking->showtime->movie->title }}</span>
                                <span class="text-slate-400">(Seat {{ $rt->bookingItem->seat->seat_code }})</span> &bull;
                                <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $rt->status === 'VALIDATED' ? 'bg-amber-950 text-amber-400' : 'bg-emerald-950 text-emerald-400' }}">
                                    {{ $rt->status }}
                                </span>
                            </div>

                            <button
                                type="button"
                                wire:click="testScanTicket('{{ $rt->ticket_code }}')"
                                class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-bold border border-slate-700 transition"
                            >
                                Test Scan This Ticket
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

