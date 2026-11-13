<x-app-layout>
    <div class="py-10 bg-slate-950 min-h-screen text-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header & Branch Switcher -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-5">
                <div>
                    <span class="text-xs uppercase tracking-widest text-emerald-400 font-bold">Executive Analytics</span>
                    <h1 class="text-3xl font-black text-white">Sales & Occupancy Reports</h1>
                    <p class="text-xs text-slate-400 mt-1">Reconciled financial audits, branch performance, and live operational stats.</p>
                </div>

                @if (auth()->user()->isAdmin())
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
                        <label class="text-xs text-slate-400 font-semibold uppercase">Branch:</label>
                        <select name="cinema_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                            <option value="">All Branches (Consolidated)</option>
                            @foreach ($cinemas as $c)
                                <option value="{{ $c->id }}" @selected($cinemaId == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <div class="bg-slate-900 border border-slate-800 px-4 py-2 rounded-xl text-xs">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">Manager Scope</span>
                        <strong class="text-emerald-400">{{ $cinemas->first()?->name }}</strong>
                    </div>
                @endif
            </div>

            <!-- Financial Key Metrics Grid (LKR Cents Reconciled) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                
                <!-- Net Revenue -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-bl-full pointer-events-none"></div>
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Net Revenue (LKR)</span>
                    <div class="text-3xl font-black text-emerald-400 font-mono mt-2">
                        Rs. {{ number_format($netSalesMinor / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Reconciled after refunds</div>
                </div>

                <!-- Online Sales -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Online Payments</span>
                    <div class="text-2xl font-black text-cyan-400 font-mono mt-2">
                        Rs. {{ number_format($onlineSalesMinor / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Payment gateway transactions</div>
                </div>

                <!-- Counter Cash -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Counter Box Office Cash</span>
                    <div class="text-2xl font-black text-indigo-400 font-mono mt-2">
                        Rs. {{ number_format($cashSalesMinor / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Direct cashier cash collected</div>
                </div>

                <!-- Total Refunds -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Processed Refunds</span>
                    <div class="text-2xl font-black text-rose-400 font-mono mt-2">
                        Rs. {{ number_format($totalRefundsMinor / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Cancelled booking returns</div>
                </div>

            </div>

            <!-- Branch Occupancy & Capacity Performance -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl mb-10">
                <h2 class="text-lg font-bold text-white mb-4 border-b border-slate-800 pb-3 flex items-center justify-between">
                    <span>Branch Occupancy & Capacity Tracking</span>
                    <span class="text-xs text-slate-400 font-normal">Based on 72 active showtimes across 6 screens</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($occupancyData as $item)
                        <div class="bg-slate-950 p-5 rounded-xl border border-slate-800">
                            <h3 class="font-bold text-white text-base">{{ $item['cinema']->name }}</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $item['cinema']->city }}</p>

                            <div class="mt-4 flex items-end justify-between">
                                <div>
                                    <span class="text-2xl font-black text-white font-mono">{{ $item['soldSeats'] }}</span>
                                    <span class="text-xs text-slate-500">/ {{ $item['totalInventory'] }} seats</span>
                                </div>
                                <span class="text-lg font-black text-emerald-400 font-mono">{{ $item['occupancyRate'] }}%</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full h-2 bg-slate-800 rounded-full mt-2 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" style="width: {{ min(100, $item['occupancyRate']) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Audit Trail & System Events -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white">System Audit Trail</h2>
                    <span class="text-xs text-slate-400">Real-time actions, cancellations, & POS events</span>
                </div>

                @if ($auditLogs->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-500">
                        No audit events recorded yet. Confirm a booking or cash sale to generate audit entries.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-950 text-slate-400 uppercase tracking-wider font-bold border-b border-slate-800">
                                    <th class="py-3 px-4">Timestamp</th>
                                    <th class="py-3 px-4">Action</th>
                                    <th class="py-3 px-4">Actor</th>
                                    <th class="py-3 px-4">Branch</th>
                                    <th class="py-3 px-4">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach ($auditLogs as $log)
                                    <tr class="hover:bg-slate-800/40">
                                        <td class="py-3 px-4 text-slate-400 font-mono">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="py-3 px-4">
                                            <span class="bg-slate-800 px-2 py-0.5 rounded font-mono font-bold text-emerald-400 border border-slate-700">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-white font-semibold">{{ $log->actor?->name ?? 'System' }}</td>
                                        <td class="py-3 px-4 text-slate-300">{{ $log->cinema?->name ?? 'General' }}</td>
                                        <td class="py-3 px-4 text-slate-400 font-mono">
                                            @if ($log->details)
                                                {{ json_encode($log->details) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

