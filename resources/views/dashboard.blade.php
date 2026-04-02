<x-app-layout>
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
        $firstName = explode(' ', auth()->user()->name)[0];
        $month = now()->month;
        $season = match(true) {
            $month >= 3 && $month <= 5 => 'Spring',
            $month >= 6 && $month <= 8 => 'Summer',
            $month >= 9 && $month <= 11 => 'Autumn',
            default => 'Winter',
        };
    @endphp

    <div class="space-y-6">

        {{-- Greeting --}}
        <div>
            <h1 class="text-2xl font-bold text-base-content">
                Good {{ $greeting }}, {{ $firstName }}! 🐝
            </h1>
            <p class="text-base-content/50 mt-0.5 text-sm">
                {{ now()->format('l, F j, Y') }} &middot; {{ $season }} season
            </p>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Active Hives</p>
                            <p class="text-3xl font-bold text-base-content mt-1.5">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-primary/15 flex items-center justify-center text-xl">
                            🏠
                        </div>
                    </div>
                    <p class="text-xs text-base-content/40 mt-3 border-t border-base-200 pt-3">
                        No hives added yet
                    </p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">This Month</p>
                            <p class="text-3xl font-bold text-base-content mt-1.5">0</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-secondary/15 flex items-center justify-center text-xl">
                            📋
                        </div>
                    </div>
                    <p class="text-xs text-base-content/40 mt-3 border-t border-base-200 pt-3">
                        Inspections completed
                    </p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Avg Health Score</p>
                            <p class="text-3xl font-bold text-base-content/25 mt-1.5">—</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-success/15 flex items-center justify-center text-xl">
                            💚
                        </div>
                    </div>
                    <p class="text-xs text-base-content/40 mt-3 border-t border-base-200 pt-3">
                        No inspection data yet
                    </p>
                </div>
            </div>

        </div>

        {{-- Recent activity / empty state --}}
        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body p-6">
                <h2 class="font-semibold text-base text-base-content">Recent Inspections</h2>

                <div class="py-14 flex flex-col items-center text-center">
                    <div class="text-5xl mb-4 opacity-80">🐝</div>
                    <h3 class="font-semibold text-base-content">Your apiary awaits</h3>
                    <p class="text-sm text-base-content/50 mt-1.5 max-w-sm">
                        Add your first hive, then record inspections in plain text — the AI will parse your notes into structured fields automatically.
                    </p>
                    <div class="mt-6 flex flex-col items-center gap-2">
                        <button class="btn btn-primary btn-sm opacity-50 cursor-not-allowed" disabled>
                            + Add Your First Hive
                        </button>
                        <span class="text-xs text-base-content/30">Hive management coming next</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI tip card --}}
        <div class="card bg-primary/8 border border-primary/20">
            <div class="card-body p-5">
                <div class="flex gap-4 items-start">
                    <div class="text-2xl shrink-0 mt-0.5">✨</div>
                    <div>
                        <p class="font-semibold text-sm text-base-content">AI-powered inspections</p>
                        <p class="text-sm text-base-content/60 mt-0.5 leading-relaxed">
                            When you record an inspection, just type your notes naturally —
                            <em>"queen seen, good brood pattern, added a super"</em> —
                            and the AI fills in all the structured fields for you.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
