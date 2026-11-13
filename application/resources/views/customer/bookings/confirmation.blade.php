<x-app-layout>
    <div class="py-12 bg-slate-950 min-h-screen text-slate-100 print:bg-white print:text-black">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Success Notification Card (Hidden on Print) -->
            <div class="mb-8 bg-emerald-950/80 border border-emerald-500 rounded-2xl p-6 shadow-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-2xl shrink-0 shadow-lg shadow-emerald-500/30">
                        &#10003;
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Payment & Reservation Complete</span>
                        <h1 class="text-2xl font-black text-white">Booking Confirmed!</h1>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Confirmation has been issued to <strong class="text-white">{{ $booking->customer_email }}</strong>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button onclick="window.print()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Tickets
                    </button>
                    <a href="{{ route('movies.index') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 rounded-xl text-xs font-black transition">
                        Browse More
                    </a>
                </div>
            </div>

            <!-- Printable Booking Pass Container -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl print:border-none print:shadow-none print:p-0">
                
                <!-- Booking Master Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-6 mb-6">
                    <div>
                        <span class="text-xs uppercase tracking-widest text-emerald-400 font-bold">Admission E-Tickets</span>
                        <h2 class="text-3xl font-black text-white mt-1">{{ $booking->showtime->movie->title }}</h2>
                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-1">
                            <span class="text-white font-semibold">{{ $booking->showtime->screen->cinema->name }}</span> &bull;
                            <span>{{ $booking->showtime->screen->name }}</span> &bull;
                            <span class="text-emerald-400 font-bold">{{ $booking->showtime->starts_at->format('l, d M Y - h:i A') }}</span>
                        </div>
                    </div>

                    <div class="text-left sm:text-right bg-slate-950/80 border border-slate-800 px-4 py-3 rounded-2xl">
                        <div class="text-[10px] uppercase font-bold text-slate-400">Booking Reference</div>
                        <div class="text-xl font-mono font-black text-emerald-400">{{ $booking->reference }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Status: <strong class="text-emerald-400">{{ $booking->status }}</strong></div>
                    </div>
                </div>

                <!-- Individual Admission Tickets with QR Codes -->
                <div class="mb-8">
                    <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-4">
                        Individual Seat Tickets ({{ count($ticketsWithQr) }})
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($ticketsWithQr as $item)
                            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 flex items-center justify-between gap-4 shadow print:border-gray-300">
                                
                                <div class="space-y-1 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs uppercase font-bold text-slate-500">Seat Code</span>
                                        <span class="text-xs bg-indigo-950 border border-indigo-700 text-indigo-300 px-2 py-0.5 rounded font-bold">
                                            {{ $item['seat']->seat_type }}
                                        </span>
                                    </div>
                                    <div class="text-3xl font-black text-white font-mono">
                                        {{ $item['seat']->seat_code }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-medium">
                                        Row {{ $item['seat']->row_label }} &bull; Seat {{ $item['seat']->seat_number }}
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-500 pt-1">
                                        Ticket #{{ $item['ticket']->ticket_code }}
                                    </div>
                                    <div class="text-[10px] text-emerald-400 font-semibold">
                                        &#9679; {{ $item['ticket']->status }} FOR ADMISSION
                                    </div>
                                </div>

                                <!-- Inline QR Code SVG -->
                                <div class="bg-white p-2 rounded-xl shrink-0 shadow-md">
                                    <div class="w-24 h-24">
                                        {!! $item['qrSvg'] !!}
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Transaction Summary Table -->
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-6">
                    <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-3">Payment Receipt Details</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <span class="text-slate-500 block">Payment Method</span>
                            <span class="text-white font-bold">{{ $booking->latestPayment?->method ?? 'MOCK_ONLINE' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Transaction Reference</span>
                            <span class="text-emerald-400 font-mono font-bold">{{ $booking->latestPayment?->transaction_reference ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Total Amount Paid</span>
                            <span class="text-white font-bold text-base">Rs. {{ number_format($booking->total_minor / 100, 2) }}</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>

