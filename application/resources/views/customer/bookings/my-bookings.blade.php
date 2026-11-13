<x-app-layout>
    <div class="py-10 bg-slate-950 min-h-screen text-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-5">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">My Ticket Bookings</h1>
                    <p class="text-sm text-slate-400 mt-1">Review past and upcoming reservations, download tickets, or request cancellations.</p>
                </div>

                <a href="{{ route('movies.index') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-xl text-xs transition">
                    Book New Tickets
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 bg-emerald-950/80 border border-emerald-500 text-emerald-200 px-5 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-rose-950/80 border border-rose-500 text-rose-200 px-5 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($bookings->isEmpty())
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
                    <svg class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    <h3 class="text-lg font-bold text-slate-300">No bookings found</h3>
                    <p class="text-xs text-slate-500 mt-1">You haven't made any reservations yet.</p>
                    <a href="{{ route('movies.index') }}" class="inline-block mt-4 px-4 py-2 bg-emerald-500 text-slate-950 text-xs font-black rounded-xl">Browse Movies</a>
                </div>
            @else
                <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-950/70 border-b border-slate-800 text-slate-400 uppercase tracking-wider font-bold">
                                    <th class="py-3.5 px-4">Booking Ref</th>
                                    <th class="py-3.5 px-4">Movie & Cinema</th>
                                    <th class="py-3.5 px-4">Showtime</th>
                                    <th class="py-3.5 px-4">Seats</th>
                                    <th class="py-3.5 px-4">Total</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                @foreach ($bookings as $b)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="py-4 px-4 font-mono font-bold text-emerald-400">
                                            {{ $b->reference }}
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-white text-sm">{{ $b->showtime->movie->title }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $b->showtime->screen->cinema->name }} &bull; {{ $b->showtime->screen->name }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-slate-300">
                                            {{ $b->showtime->starts_at->format('d M Y') }}<br>
                                            <span class="text-emerald-400 font-semibold">{{ $b->showtime->starts_at->format('h:i A') }}</span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($b->items as $item)
                                                    <span class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 text-white font-mono font-bold">
                                                        {{ $item->seat->seat_code }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 font-bold text-white">
                                            Rs. {{ number_format($b->total_minor / 100, 2) }}
                                        </td>
                                        <td class="py-4 px-4">
                                            @php
                                                $badgeClass = match($b->status) {
                                                    'CONFIRMED' => 'bg-emerald-950/60 border-emerald-500 text-emerald-300',
                                                    'CANCELLED' => 'bg-rose-950/60 border-rose-500 text-rose-300',
                                                    'PENDING' => 'bg-amber-950/60 border-amber-500 text-amber-300',
                                                    default => 'bg-slate-800 border-slate-700 text-slate-300',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                                {{ $b->status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right space-x-2">
                                            @if ($b->status === 'CONFIRMED')
                                                <a href="{{ route('bookings.confirmation', $b->id) }}" class="inline-block px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg font-bold">
                                                    View Tickets
                                                </a>
                                                <form method="POST" action="{{ route('bookings.cancel', $b->id) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to cancel this booking? Seats will be returned to inventory and simulated refund issued.');">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-rose-950/40 hover:bg-rose-900 border border-rose-800 text-rose-300 rounded-lg font-bold">
                                                        Cancel
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-slate-600 italic">None</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($bookings->hasPages())
                        <div class="p-4 border-t border-slate-800">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

