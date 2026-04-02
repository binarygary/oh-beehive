<?php

use App\Enums\HiveStatus;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $location = '';
    public string $acquiredAt = '';
    public string $status = 'active';
    public string $notes = '';

    public function save(): void
    {
        $this->validate([
            'name'       => ['required', 'string', 'max:255'],
            'location'   => ['nullable', 'string', 'max:255'],
            'acquiredAt' => ['nullable', 'date'],
            'status'     => ['required', Rule::enum(HiveStatus::class)],
            'notes'      => ['nullable', 'string'],
        ]);

        auth()->user()->hives()->create([
            'name'        => $this->name,
            'location'    => $this->location ?: null,
            'acquired_at' => $this->acquiredAt ?: null,
            'status'      => $this->status,
            'notes'       => $this->notes ?: null,
        ]);

        $this->redirect(route('hives.index'), navigate: true);
    }
}; ?>

<x-app-layout>
    <div class="max-w-xl space-y-6">

        {{-- Back link + heading --}}
        <div>
            <a href="{{ route('hives.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm text-base-content/50 hover:text-base-content transition-colors mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                My Hives
            </a>
            <h1 class="text-2xl font-bold text-base-content">Add a Hive</h1>
        </div>

        {{-- Form --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-6">
                <form wire:submit="save" class="space-y-5">

                    {{-- Name --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-base-content">
                            Name <span class="text-error">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            class="input input-bordered w-full"
                            placeholder="e.g. North Meadow #1"
                            autofocus
                        />
                        @error('name')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Location --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-base-content">Location</label>
                        <input
                            type="text"
                            wire:model="location"
                            class="input input-bordered w-full"
                            placeholder="e.g. Back garden, south fence"
                        />
                        @error('location')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status + Acquired side by side --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Status</label>
                            <select wire:model="status" class="select select-bordered w-full">
                                @foreach(HiveStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="text-error text-xs">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Date Acquired</label>
                            <input
                                type="date"
                                wire:model="acquiredAt"
                                class="input input-bordered w-full"
                            />
                            @error('acquiredAt')
                                <p class="text-error text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-base-content">Notes</label>
                        <textarea
                            wire:model="notes"
                            rows="3"
                            class="textarea textarea-bordered w-full resize-none"
                            placeholder="Any background info about this hive..."
                        ></textarea>
                        @error('notes')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('hives.index') }}" wire:navigate class="btn btn-ghost btn-sm">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span wire:loading.remove>Save Hive</span>
                            <span wire:loading>Saving…</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</x-app-layout>
