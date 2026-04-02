<?php

use App\Models\Hive;
use Livewire\Volt\Component;

new class extends Component
{
    public function delete(int $id): void
    {
        $hive = auth()->user()->hives()->findOrFail($id);
        $hive->delete();
    }

    public function with(): array
    {
        return [
            'hives' => auth()->user()
                ->hives()
                ->withCount('inspections')
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<x-app-layout>
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content">My Hives</h1>
                <p class="text-sm text-base-content/50 mt-0.5">
                    {{ $hives->count() }} {{ Str::plural('hive', $hives->count()) }}
                </p>
            </div>
            <a href="{{ route('hives.create') }}" wire:navigate class="btn btn-primary btn-sm gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                New Hive
            </a>
        </div>

        {{-- Empty state --}}
        @if($hives->isEmpty())
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body py-16 flex flex-col items-center text-center">
                    <div class="text-5xl mb-4">🏠</div>
                    <h3 class="font-semibold text-base-content">No hives yet</h3>
                    <p class="text-sm text-base-content/50 mt-1 max-w-xs">
                        Add your first hive to start recording inspections.
                    </p>
                    <a href="{{ route('hives.create') }}" wire:navigate class="btn btn-primary btn-sm mt-6">
                        + Add Your First Hive
                    </a>
                </div>
            </div>

        {{-- Hive grid --}}
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($hives as $hive)
                    <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow">
                        <div class="card-body p-5">

                            {{-- Name + status --}}
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-bold text-base-content text-lg leading-tight">
                                    {{ $hive->name }}
                                </h2>
                                <span class="badge badge-sm shrink-0 {{ $hive->status->badgeClass() }}">
                                    {{ $hive->status->label() }}
                                </span>
                            </div>

                            {{-- Meta --}}
                            <div class="mt-2 space-y-1">
                                @if($hive->location)
                                    <div class="flex items-center gap-1.5 text-sm text-base-content/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $hive->location }}
                                    </div>
                                @endif
                                @if($hive->acquired_at)
                                    <div class="flex items-center gap-1.5 text-sm text-base-content/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Acquired {{ $hive->acquired_at->format('M j, Y') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Divider + footer --}}
                            <div class="mt-4 pt-3 border-t border-base-200 flex items-center justify-between">
                                <span class="text-xs text-base-content/40">
                                    {{ $hive->inspections_count }} {{ Str::plural('inspection', $hive->inspections_count) }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('hives.edit', $hive) }}" wire:navigate
                                       class="btn btn-ghost btn-xs gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </a>
                                    <button
                                        wire:click="delete({{ $hive->id }})"
                                        wire:confirm="Delete '{{ $hive->name }}'? This cannot be undone."
                                        class="btn btn-ghost btn-xs text-error gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-app-layout>
