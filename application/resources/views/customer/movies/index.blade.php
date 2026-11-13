<x-app-layout>
    <div class="bg-slate-950 min-h-screen text-slate-100 pb-16">
        
        <!-- Hero Header -->
        <div class="relative bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border-b border-slate-800 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="inline-block px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold rounded-full mb-3 uppercase tracking-wider">
                        Sri Lanka Cinema Network
                    </span>
                    <h1 class="text-4xl sm:text-5xl font-black text-white tracking-tight">
                        Experience Cinema Like Never Before
                    </h1>
                    <p class="text-slate-400 mt-2 text-sm sm:text-base max-w-2xl">
                        Book tickets across Colombo, Kandy, and Galle. Select your favorite seats in real-time with instant digital QR tickets.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('my-bookings') }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        My Bookings
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
            <form method="GET" action="{{ route('movies.index') }}" class="bg-slate-900/90 backdrop-blur-md border border-slate-800 rounded-2xl p-4 shadow-2xl grid grid-cols-1 sm:grid-cols-4 gap-4">
                
                <!-- Cinema Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Cinema Branch</label>
                    <select name="cinema" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="">All Cinemas (Island-wide)</option>
                        @foreach ($cinemas as $cinema)
                            <option value="{{ $cinema->id }}" @selected($selectedCinemaId == $cinema->id)>
                                {{ $cinema->name }} ({{ $cinema->city }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Genre Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Genre</label>
                    <select name="genre" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="">All Genres</option>
                        @foreach ($genres as $genre)
                            <option value="{{ $genre->id }}" @selected($selectedGenreId == $genre->id)>
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Date</label>
                    <input
                        type="date"
                        name="date"
                        value="{{ $selectedDate }}"
                        onchange="this.form.submit()"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <!-- Reset / Filter Button -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-xl text-xs transition">
                        Filter Screenings
                    </button>
                    @if ($selectedCinemaId || $selectedGenreId || $selectedDate !== \Carbon\Carbon::today()->toDateString())
                        <a href="{{ route('movies.index') }}" class="py-2 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl text-xs border border-slate-700 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Movie Catalog Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <span>Now Showing</span>
                    <span class="text-xs bg-slate-800 text-emerald-400 border border-slate-700 px-2.5 py-0.5 rounded-full font-semibold">
                        {{ $movies->count() }} Movies
                    </span>
                </h2>
                <div class="text-xs text-slate-400">
                    Showing schedules for <strong class="text-emerald-400">{{ \Carbon\Carbon::parse($selectedDate)->format('D, d M Y') }}</strong>
                </div>
            </div>

            @if ($movies->isEmpty())
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
                    <svg class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    <h3 class="text-lg font-bold text-slate-300">No screenings found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try selecting a different cinema branch, genre, or date.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($movies as $movie)
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl hover:border-slate-700 transition flex flex-col group">
                            
                            <!-- Poster Area -->
                            <div class="relative h-64 overflow-hidden bg-slate-950">
                                @if ($movie->poster_url)
                                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.src='https://placehold.co/400x600/0f172a/38bdf8?text={{ urlencode($movie->title) }}'" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-slate-950 text-slate-600 font-bold">No Poster</div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1">
                                    <span class="bg-slate-950/80 backdrop-blur-md border border-slate-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ $movie->rating }}
                                    </span>
                                    <span class="bg-slate-950/80 backdrop-blur-md border border-slate-700 text-slate-300 text-[10px] font-medium px-2 py-0.5 rounded-full">
                                        {{ $movie->duration_minutes }} min
                                    </span>
                                </div>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h3 class="text-xl font-black text-white drop-shadow-md">{{ $movie->title }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach ($movie->genres as $genre)
                                            <span class="text-[10px] text-emerald-400 font-semibold">{{ $genre->name }}</span>@if(!$loop->last)&bull;@endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Showtimes List for Selected Date -->
                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
                                        <span>Available Showtimes</span>
                                        <a href="{{ route('movies.show', $movie->slug) }}" class="text-emerald-400 hover:text-emerald-300">View Details &rarr;</a>
                                    </div>

                                    @if ($movie->showtimes->isEmpty())
                                        <p class="text-xs text-slate-500 italic py-2">No screenings scheduled for this cinema or date.</p>
                                    @else
                                        <div class="space-y-2.5">
                                            @foreach ($movie->showtimes->groupBy(fn($st) => $st->screen->cinema->name) as $cinemaName => $cinemaShowtimes)
                                                <div class="bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/80">
                                                    <div class="text-[11px] font-bold text-slate-300 mb-1.5 flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span>{{ $cinemaName }}</span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach ($cinemaShowtimes as $st)
                                                            <a
                                                                href="{{ route('showtimes.seats', $st->id) }}"
                                                                class="px-2.5 py-1 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-300 hover:text-slate-950 border border-emerald-500/40 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                            >
                                                                <span>{{ $st->starts_at->format('h:i A') }}</span>
                                                                <span class="text-[9px] opacity-75 font-normal">({{ $st->screen->name }})</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                                    <span>From <strong class="text-white">Rs. 1,000.00</strong></span>
                                    <a href="{{ route('movies.show', $movie->slug) }}" class="text-xs font-bold text-emerald-400 hover:underline">
                                        View Schedule &rarr;
                                    </a>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

