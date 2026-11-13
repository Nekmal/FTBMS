<x-app-layout>
    <div class="bg-slate-950 min-h-screen text-slate-100 pb-20">
        
        <!-- Movie Banner & Details Header -->
        <div class="relative bg-slate-900 border-b border-slate-800 py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-8 items-start">
                
                <!-- Poster -->
                <div class="w-48 sm:w-60 shrink-0 rounded-2xl overflow-hidden shadow-2xl border border-slate-700 bg-slate-950">
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="w-full h-auto object-cover" onerror="this.src='https://placehold.co/400x600/0f172a/38bdf8?text={{ urlencode($movie->title) }}'" />
                </div>

                <!-- Info -->
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-bold uppercase">
                            {{ $movie->status }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 text-xs font-semibold">
                            {{ $movie->rating }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 text-xs font-semibold">
                            {{ $movie->duration_minutes }} Minutes
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 text-xs font-semibold">
                            Language: {{ $movie->language }}
                        </span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight">{{ $movie->title }}</h1>
                    
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($movie->genres as $genre)
                            <span class="text-xs text-indigo-400 font-semibold bg-indigo-950/60 border border-indigo-800/60 px-2.5 py-0.5 rounded-lg">
                                {{ $genre->name }}
                            </span>
                        @endforeach
                    </div>

                    <p class="text-slate-300 text-sm sm:text-base mt-4 leading-relaxed max-w-3xl">
                        {{ $movie->description }}
                    </p>

                    <div class="mt-4 text-xs text-slate-500">
                        Release Date: <span class="text-slate-300 font-semibold">{{ $movie->release_date?->format('d F Y') }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Showtime Selection Area -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
            
            <!-- Date Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-4 border-b border-slate-800 mb-8">
                @foreach (range(0, 4) as $offset)
                    @php
                        $targetDate = \Carbon\Carbon::today()->addDays($offset);
                        $formattedValue = $targetDate->toDateString();
                        $isActive = ($selectedDate === $formattedValue);
                    @endphp
                    <a
                        href="{{ route('movies.show', ['movie' => $movie->slug, 'date' => $formattedValue, 'cinema' => $selectedCinemaId]) }}"
                        class="px-5 py-3 rounded-2xl border text-center transition shrink-0 {{ $isActive ? 'bg-emerald-500 border-emerald-400 text-slate-950 font-black shadow-lg shadow-emerald-500/20' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-white font-semibold' }}"
                    >
                        <div class="text-[10px] uppercase tracking-wider {{ $isActive ? 'text-slate-900' : 'text-slate-500' }}">
                            {{ $offset === 0 ? 'Today' : ($offset === 1 ? 'Tomorrow' : $targetDate->format('D')) }}
                        </div>
                        <div class="text-sm font-bold">
                            {{ $targetDate->format('d M') }}
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Showtimes Grouped by Cinema -->
            <div class="space-y-6">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>Available Showtimes on</span>
                    <span class="text-emerald-400">{{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}</span>
                </h2>

                @if ($showtimes->isEmpty())
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-10 text-center">
                        <p class="text-slate-400 text-sm">No scheduled screenings available on this date for the selected criteria.</p>
                        <p class="text-xs text-slate-500 mt-1">Please choose another date above.</p>
                    </div>
                @else
                    @foreach ($showtimes as $cinemaName => $cinemaShowtimes)
                        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow-xl">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white">{{ $cinemaName }}</h3>
                                        <p class="text-xs text-slate-400">{{ $cinemaShowtimes->first()->screen->cinema->address }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-400 font-medium">
                                    Standard: <strong class="text-emerald-400">Rs. 1,000</strong> &bull;
                                    VIP: <strong class="text-amber-400">Rs. 2,000</strong>
                                </span>
                            </div>

                            <!-- Screen Showtimes -->
                            <div class="flex flex-wrap gap-4">
                                @foreach ($cinemaShowtimes as $st)
                                    <a
                                        href="{{ route('showtimes.seats', $st->id) }}"
                                        class="group bg-slate-950 border border-slate-700/80 hover:border-emerald-500 p-4 rounded-xl flex flex-col gap-1 transition shadow hover:shadow-emerald-500/10 min-w-[140px]"
                                    >
                                        <span class="text-lg font-black text-white group-hover:text-emerald-400 transition">
                                            {{ $st->starts_at->format('h:i A') }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-medium">
                                            {{ $st->screen->name }}
                                        </span>
                                        <span class="text-[10px] text-emerald-500 font-bold mt-1">
                                            Select Seats &rarr;
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        </div>

    </div>
</x-app-layout>

