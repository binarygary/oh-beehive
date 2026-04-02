<?php

use App\Models\Inspection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function delete(int $id): void
    {
        auth()->user()->inspections()->findOrFail($id)->delete();
    }

    public function with(): array
    {
        return [
            'inspections' => auth()->user()
                ->inspections()
                ->with('hive')
                ->orderByDesc('inspected_at')
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Inspections</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                {{ $inspections->count() }} {{ Str::plural('inspection', $inspections->count()) }}
            </p>
        </div>
        <a href="{{ route('inspections.create') }}" wire:navigate class="btn btn-primary btn-sm gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Inspection
        </a>
    </div>

    {{-- Empty state --}}
    @if($inspections->isEmpty())
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body py-16 flex flex-col items-center text-center">
                <div class="text-5xl mb-4">📋</div>
                <h3 class="font-semibold text-base-content">No inspections yet</h3>
                <p class="text-sm text-base-content/50 mt-1 max-w-xs">
                    Record your first inspection by typing your observations in plain text.
                </p>
                <a href="{{ route('inspections.create') }}" wire:navigate class="btn btn-primary btn-sm mt-6">
                    + Record First Inspection
                </a>
            </div>
        </div>

    {{-- Inspection table --}}
    @else
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="border-base-300">
                            <th class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Date</th>
                            <th class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Hive</th>
                            <th class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Notes</th>
                            <th class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Health</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inspections as $inspection)
                            <tr class="border-base-300 hover:bg-base-200/50">
                                <td class="whitespace-nowrap text-sm">
                                    {{ $inspection->inspected_at->format('M j, Y') }}
                                    <span class="text-base-content/40 text-xs block">{{ $inspection->inspected_at->format('g:i a') }}</span>
                                </td>
                                <td class="text-sm font-medium">{{ $inspection->hive->name }}</td>
                                <td class="text-sm text-base-content/60 max-w-xs">
                                    {{ Str::limit($inspection->raw_notes, 90) }}
                                </td>
                                <td>
                                    @if($inspection->overall_health_score)
                                        @php
                                            $score = $inspection->overall_health_score;
                                            $cls = $score >= 4 ? 'badge-success' : ($score >= 3 ? 'badge-warning' : 'badge-error');
                                        @endphp
                                        <span class="badge badge-sm {{ $cls }}">{{ $score }}/5</span>
                                    @else
                                        <span class="text-base-content/30 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('inspections.edit', $inspection) }}" wire:navigate
                                       class="btn btn-ghost btn-xs gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </a>
                                    <button
                                        wire:click="delete({{ $inspection->id }})"
                                        wire:confirm="Delete this inspection? This cannot be undone."
                                        class="btn btn-ghost btn-xs text-error gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
