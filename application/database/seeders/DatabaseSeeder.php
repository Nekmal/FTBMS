<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Promotion;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\SeatingLayout;
use App\Models\ShowSeat;
use App\Models\Showtime;
use App\Models\ShowtimePrice;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        $password = Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@bms.lk'],
            ['name' => 'System Administrator', 'password' => $password]
        );
        UserRole::firstOrCreate(['user_id' => $admin->id, 'role' => 'ADMIN', 'cinema_id' => null]);

        // 2. Create 3 Sample Sri Lankan Cinemas
        $cinemasData = [
            [
                'name' => 'Regal Cinema - Colombo',
                'city' => 'Colombo',
                'address' => '65 Sir Chittampalam A Gardiner Mawatha, Colombo 02',
                'phone' => '+94 11 232 5431',
                'email' => 'regal.colombo@bms.lk',
            ],
            [
                'name' => 'Liberty Cinema - Kandy',
                'city' => 'Kandy',
                'address' => '123 Dalada Veediya, Kandy',
                'phone' => '+94 81 223 4567',
                'email' => 'liberty.kandy@bms.lk',
            ],
            [
                'name' => 'Majestic Cineplex - Galle',
                'city' => 'Galle',
                'address' => '45 Wakwella Road, Galle',
                'phone' => '+94 91 224 8901',
                'email' => 'majestic.galle@bms.lk',
            ],
        ];

        $cinemas = [];
        foreach ($cinemasData as $index => $cData) {
            $cinema = Cinema::firstOrCreate(['name' => $cData['name']], $cData);
            $cinemas[] = $cinema;

            // Cinema Manager for each branch
            $slugCity = strtolower(explode(' ', $cData['city'])[0]);
            $mgr = User::firstOrCreate(
                ['email' => "manager.{$slugCity}@bms.lk"],
                ['name' => "{$cData['city']} Cinema Manager", 'password' => $password]
            );
            UserRole::firstOrCreate(['user_id' => $mgr->id, 'role' => 'CINEMA_MANAGER', 'cinema_id' => $cinema->id]);

            // Staff for each branch
            $stf = User::firstOrCreate(
                ['email' => "staff.{$slugCity}@bms.lk"],
                ['name' => "{$cData['city']} Counter Staff", 'password' => $password]
            );
            UserRole::firstOrCreate(['user_id' => $stf->id, 'role' => 'COUNTER_STAFF', 'cinema_id' => $cinema->id]);
        }

        // Demo customer
        $customer = User::firstOrCreate(
            ['email' => 'customer@bms.lk'],
            ['name' => 'Nimal Perera', 'password' => $password]
        );
        UserRole::firstOrCreate(['user_id' => $customer->id, 'role' => 'CUSTOMER', 'cinema_id' => null]);

        // 3. Create Screens and Seating Layouts (2 screens per cinema, 50-60 seats each)
        $createdScreens = [];
        foreach ($cinemas as $cinema) {
            // Screen 1: 60 seats (6 rows x 10 seats)
            $screen1 = Screen::firstOrCreate(
                ['cinema_id' => $cinema->id, 'name' => 'Screen 1 (Dolby Atmos)'],
                ['total_capacity' => 60]
            );
            $layout1 = SeatingLayout::firstOrCreate(
                ['screen_id' => $screen1->id, 'version' => 1],
                ['name' => 'Standard Atmos Layout', 'total_rows' => 6, 'seats_per_row' => 10]
            );

            // Screen 2: 40 seats (4 rows x 10 seats)
            $screen2 = Screen::firstOrCreate(
                ['cinema_id' => $cinema->id, 'name' => 'Screen 2 (Digital 3D)'],
                ['total_capacity' => 40]
            );
            $layout2 = SeatingLayout::firstOrCreate(
                ['screen_id' => $screen2->id, 'version' => 1],
                ['name' => 'Standard 3D Layout', 'total_rows' => 4, 'seats_per_row' => 10]
            );

            // Generate Seats for Screen 1
            $this->seedSeats($layout1, 6, 10);
            // Generate Seats for Screen 2
            $this->seedSeats($layout2, 4, 10);

            $createdScreens[] = ['screen' => $screen1, 'layout' => $layout1];
            $createdScreens[] = ['screen' => $screen2, 'layout' => $layout2];
        }

        // 4. Create Genres
        $genresData = ['Action', 'Sci-Fi', 'Drama', 'Adventure', 'Animation', 'Historical'];
        $genreModels = [];
        foreach ($genresData as $gName) {
            $genreModels[$gName] = Genre::firstOrCreate(
                ['name' => $gName],
                ['slug' => Str::slug($gName)]
            );
        }

        // 5. Create Movies
        $moviesData = [
            [
                'title' => 'Dune: Part Two',
                'slug' => 'dune-part-two',
                'duration_minutes' => 166,
                'description' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
                'status' => 'NOW_SHOWING',
                'release_date' => '2024-03-01',
                'language' => 'English',
                'rating' => 'PG-13',
                'genres' => ['Action', 'Sci-Fi', 'Adventure'],
            ],
            [
                'title' => 'Oppenheimer',
                'slug' => 'oppenheimer',
                'duration_minutes' => 180,
                'description' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
                'status' => 'NOW_SHOWING',
                'release_date' => '2023-07-21',
                'language' => 'English',
                'rating' => 'R',
                'genres' => ['Drama', 'Historical'],
            ],
            [
                'title' => 'Spider-Man: Beyond the Spider-Verse',
                'slug' => 'spider-man-beyond-the-spider-verse',
                'duration_minutes' => 140,
                'description' => 'Miles Morales catapults across the Multiverse to stop Spot and save those he loves the most.',
                'poster_url' => 'https://image.tmdb.org/t/p/w500/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg',
                'status' => 'NOW_SHOWING',
                'release_date' => '2025-06-15',
                'language' => 'English',
                'rating' => 'PG',
                'genres' => ['Action', 'Animation', 'Adventure'],
            ],
            [
                'title' => 'Gaadi (Children of the Sun)',
                'slug' => 'gaadi-children-of-the-sun',
                'duration_minutes' => 104,
                'description' => 'Acclaimed Sri Lankan period drama by Prasanna Vithanage depicting historical caste relations in 1814.',
                'poster_url' => 'https://upload.wikimedia.org/wikipedia/en/3/30/Gaadi_film_poster.jpg',
                'status' => 'NOW_SHOWING',
                'release_date' => '2023-01-20',
                'language' => 'Sinhala',
                'rating' => 'PG-13',
                'genres' => ['Drama', 'Historical'],
            ],
            [
                'title' => 'Sinhabahu',
                'slug' => 'sinhabahu',
                'duration_minutes' => 125,
                'description' => 'A visually stunning cinematic portrayal of the ancient Sri Lankan legend directed by Dr. Somaratne Dissanayake.',
                'poster_url' => 'https://upload.wikimedia.org/wikipedia/en/9/90/Sinhabahu_Poster.jpg',
                'status' => 'NOW_SHOWING',
                'release_date' => '2024-05-19',
                'language' => 'Sinhala',
                'rating' => 'PG',
                'genres' => ['Drama', 'Historical'],
            ],
        ];

        $movieModels = [];
        foreach ($moviesData as $mData) {
            $genres = $mData['genres'];
            unset($mData['genres']);
            $movie = Movie::firstOrCreate(['slug' => $mData['slug']], $mData);
            $movieModels[] = $movie;

            $genreIds = array_map(fn($g) => $genreModels[$g]->id, $genres);
            $movie->genres()->sync($genreIds);
        }

        // 6. Create Showtimes for today, tomorrow, and the next day
        $showTimesSlots = [
            '10:30:00',
            '14:00:00',
            '18:30:00',
            '21:30:00',
        ];

        $startDate = Carbon::today();
        foreach (range(0, 2) as $dayOffset) {
            $currentDate = $startDate->copy()->addDays($dayOffset);

            foreach ($createdScreens as $idx => $sc) {
                $screen = $sc['screen'];
                $layout = $sc['layout'];
                $movie = $movieModels[($idx + $dayOffset) % count($movieModels)];

                foreach ($showTimesSlots as $slotIdx => $time) {
                    $startsAt = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $time);
                    $endsAt = $startsAt->copy()->addMinutes($movie->duration_minutes);

                    $showtime = Showtime::firstOrCreate(
                        [
                            'movie_id' => $movie->id,
                            'screen_id' => $screen->id,
                            'starts_at' => $startsAt,
                        ],
                        [
                            'layout_id' => $layout->id,
                            'ends_at' => $endsAt,
                            'status' => 'SCHEDULED',
                        ]
                    );

                    // Add Pricing in LKR cents (Standard: 1,000 LKR = 100,000 cents, Premium: 1,500 LKR = 150,000, VIP: 2,000 LKR = 200,000)
                    ShowtimePrice::firstOrCreate(
                        ['showtime_id' => $showtime->id, 'seat_type' => 'STANDARD'],
                        ['price_minor' => 100000]
                    );
                    ShowtimePrice::firstOrCreate(
                        ['showtime_id' => $showtime->id, 'seat_type' => 'PREMIUM'],
                        ['price_minor' => 150000]
                    );
                    ShowtimePrice::firstOrCreate(
                        ['showtime_id' => $showtime->id, 'seat_type' => 'VIP'],
                        ['price_minor' => 200000]
                    );

                    // Initialize show_seats inventory for this showtime
                    $seats = Seat::where('layout_id', $layout->id)->get();
                    foreach ($seats as $seat) {
                        ShowSeat::firstOrCreate(
                            [
                                'showtime_id' => $showtime->id,
                                'seat_id' => $seat->id,
                            ],
                            [
                                'status' => 'AVAILABLE',
                            ]
                        );
                    }
                }
            }
        }

        // 7. Create Sample Promotions
        Promotion::firstOrCreate(
            ['code' => 'BMS10'],
            [
                'type' => 'PERCENTAGE',
                'discount_value' => 10, // 10%
                'min_spend_minor' => 100000,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        Promotion::firstOrCreate(
            ['code' => 'FLAT500'],
            [
                'type' => 'FIXED',
                'discount_value' => 50000, // Rs. 500.00
                'min_spend_minor' => 200000, // Min spend Rs. 2000.00
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ]
        );
    }

    private function seedSeats(SeatingLayout $layout, int $totalRows, int $seatsPerRow): void
    {
        $rowLetters = range('A', 'Z');
        for ($r = 0; $r < $totalRows; $r++) {
            $rowLabel = $rowLetters[$r];
            // Assign types: First 60% Standard, next 20% Premium, back rows VIP
            $type = 'STANDARD';
            if ($r >= $totalRows - 1) {
                $type = 'VIP';
            } elseif ($r >= $totalRows - 2) {
                $type = 'PREMIUM';
            }

            for ($s = 1; $s <= $seatsPerRow; $s++) {
                Seat::firstOrCreate(
                    [
                        'layout_id' => $layout->id,
                        'row_label' => $rowLabel,
                        'seat_number' => $s,
                    ],
                    [
                        'seat_type' => $type,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
