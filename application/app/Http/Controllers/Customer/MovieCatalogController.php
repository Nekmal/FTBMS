<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MovieCatalogController extends Controller
{
    public function index(Request $request)
    {
        $selectedCinemaId = $request->query('cinema');
        $selectedGenreId = $request->query('genre');
        $selectedDate = $request->query('date', Carbon::today()->toDateString());

        $cinemas = Cinema::where('is_active', true)->with('screens')->get();
        $genres = Genre::all();

        $moviesQuery = Movie::with(['genres', 'showtimes' => function ($q) use ($selectedCinemaId, $selectedDate) {
            $q->whereDate('starts_at', $selectedDate)
              ->when($selectedCinemaId, function ($sq) use ($selectedCinemaId) {
                  $sq->whereHas('screen', function ($ssq) use ($selectedCinemaId) {
                      $ssq->where('cinema_id', $selectedCinemaId);
                  });
              })
              ->with(['screen.cinema', 'prices'])
              ->orderBy('starts_at');
        }]);

        if ($selectedGenreId) {
            $moviesQuery->whereHas('genres', function ($q) use ($selectedGenreId) {
                $q->where('genres.id', $selectedGenreId);
            });
        }

        $movies = $moviesQuery->where('status', 'NOW_SHOWING')->get();

        return view('customer.movies.index', compact('movies', 'cinemas', 'genres', 'selectedCinemaId', 'selectedGenreId', 'selectedDate'));
    }

    public function show(Movie $movie, Request $request)
    {
        $selectedDate = $request->query('date', Carbon::today()->toDateString());
        $selectedCinemaId = $request->query('cinema');

        $cinemas = Cinema::where('is_active', true)->get();

        $showtimes = Showtime::with(['screen.cinema', 'prices', 'layout'])
            ->where('movie_id', $movie->id)
            ->whereDate('starts_at', $selectedDate)
            ->where('status', 'SCHEDULED')
            ->when($selectedCinemaId, function ($q) use ($selectedCinemaId) {
                $q->whereHas('screen', fn($sq) => $sq->where('cinema_id', $selectedCinemaId));
            })
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn($st) => $st->screen->cinema->name);

        return view('customer.movies.show', compact('movie', 'showtimes', 'cinemas', 'selectedDate', 'selectedCinemaId'));
    }
}

