<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-800 text-slate-100 sticky top-0 z-50 backdrop-blur-md bg-slate-900/90">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('movies.index') }}" class="flex items-center gap-2 font-black text-xl tracking-tight text-white">
                        <span class="w-8 h-8 rounded-lg bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-base shadow-md shadow-emerald-500/20">
                            B
                        </span>
                        <span>FilmBMS<span class="text-emerald-400">.lk</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <a href="{{ route('movies.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-xs font-bold transition {{ request()->routeIs('movies.*') || request()->routeIs('home') ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                        Movies & Schedules
                    </a>

                    @auth
                        <a href="{{ route('my-bookings') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-xs font-bold transition {{ request()->routeIs('my-bookings') ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                            My Bookings
                        </a>

                        @if (auth()->user()->isCounterStaff())
                            <a href="{{ route('staff.counter-sales') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-xs font-bold transition {{ request()->routeIs('staff.counter-sales') ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                                <span class="bg-indigo-950/80 border border-indigo-700 text-indigo-300 text-[10px] px-1.5 py-0.5 rounded mr-1">POS</span>
                                Counter Sales
                            </a>

                            <a href="{{ route('staff.ticket-scanner') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-xs font-bold transition {{ request()->routeIs('staff.ticket-scanner') ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                                <span class="bg-emerald-950/80 border border-emerald-700 text-emerald-300 text-[10px] px-1.5 py-0.5 rounded mr-1">Gate</span>
                                QR Scanner
                            </a>
                        @endif

                        @if (auth()->user()->isCinemaManager())
                            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-xs font-bold transition {{ request()->routeIs('admin.reports.*') ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                                Reports & Analytics
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings / User Dropdown / Login -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-slate-700 text-xs leading-4 font-bold rounded-xl text-slate-300 bg-slate-800 hover:text-white hover:bg-slate-700 focus:outline-none transition ease-in-out duration-150 gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <div>{{ auth()->user()->name }}</div>
                                <span class="text-[10px] bg-slate-900 px-1.5 py-0.5 rounded text-emerald-400 font-mono">{{ auth()->user()->primary_role }}</span>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')">
                                {{ __('My Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white transition">
                            Log In
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-black rounded-xl transition shadow-md shadow-emerald-500/20">
                            Register
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger for Mobile -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-800 bg-slate-950 p-4 space-y-2">
        <a href="{{ route('movies.index') }}" class="block text-xs font-bold py-2 text-slate-300 hover:text-emerald-400">
            Movies & Schedules
        </a>
        @auth
            <a href="{{ route('my-bookings') }}" class="block text-xs font-bold py-2 text-slate-300 hover:text-emerald-400">
                My Bookings
            </a>
            @if (auth()->user()->isCounterStaff())
                <a href="{{ route('staff.counter-sales') }}" class="block text-xs font-bold py-2 text-indigo-400">
                    Counter POS Sales
                </a>
                <a href="{{ route('staff.ticket-scanner') }}" class="block text-xs font-bold py-2 text-emerald-400">
                    Gate QR Scanner
                </a>
            @endif
            @if (auth()->user()->isCinemaManager())
                <a href="{{ route('admin.reports.index') }}" class="block text-xs font-bold py-2 text-amber-400">
                    Sales Reports
                </a>
            @endif
            <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-xs">
                <span class="text-slate-400">{{ auth()->user()->name }}</span>
                <button wire:click="logout" class="text-rose-400 font-bold">Log Out</button>
            </div>
        @else
            <div class="pt-2 flex gap-2">
                <a href="{{ route('login') }}" class="block flex-1 py-2 text-center text-xs font-bold text-white bg-slate-800 rounded-xl">Log In</a>
                <a href="{{ route('register') }}" class="block flex-1 py-2 text-center text-xs font-bold text-slate-950 bg-emerald-500 rounded-xl">Register</a>
            </div>
        @endauth
    </div>
</nav>
