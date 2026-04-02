<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside class="w-64 min-h-screen bg-base-100 border-r border-base-300 flex flex-col">

    {{-- Brand --}}
    <div class="px-5 py-5 border-b border-base-300">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-lg shadow-sm shrink-0">
                🐝
            </div>
            <div>
                <div class="font-bold text-base-content text-sm leading-tight">Oh Beehive</div>
                <div class="text-xs text-base-content/40">Hive management</div>
            </div>
        </a>
    </div>

    {{-- Navigation links --}}
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <ul class="menu menu-md w-full p-0 gap-0.5">

            <li class="menu-title text-xs tracking-widest uppercase opacity-40 mb-1">Overview</li>

            <li>
                <a href="{{ route('dashboard') }}" wire:navigate
                   @class(['active font-medium' => request()->routeIs('dashboard')])>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
            </li>

            <li class="menu-title text-xs tracking-widest uppercase opacity-40 mt-4 mb-1">Apiary</li>

            <li>
                <a href="{{ route('hives.index') }}" wire:navigate
                   @class(['active font-medium' => request()->routeIs('hives.*')])>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    My Hives
                </a>
            </li>

            <li>
                <span class="opacity-40 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Inspections
                    <span class="badge badge-ghost badge-xs ml-auto font-normal opacity-60">soon</span>
                </span>
            </li>

        </ul>
    </nav>

    {{-- User section --}}
    <div class="p-3 border-t border-base-300">
        <div class="dropdown dropdown-top w-full">
            <div tabindex="0" role="button" class="btn btn-ghost w-full justify-start gap-3 normal-case font-normal h-auto py-2 px-3 min-h-0">
                <div class="avatar placeholder shrink-0">
                    <div class="bg-primary text-primary-content rounded-full w-8 text-sm font-bold">
                        <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                </div>
                <div class="flex-1 text-left min-w-0">
                    <div class="text-sm font-medium text-base-content truncate"
                         x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                         x-text="name"
                         x-on:profile-updated.window="name = $event.detail.name">
                    </div>
                    <div class="text-xs text-base-content/40 truncate">{{ auth()->user()->email }}</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>
            </div>
            <ul tabindex="0" class="dropdown-content menu menu-sm bg-base-100 rounded-box border border-base-300 shadow-lg z-50 w-full mb-1 p-1">
                <li>
                    <a href="{{ route('profile') }}" wire:navigate class="gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile
                    </a>
                </li>
                <li>
                    <button wire:click="logout" class="gap-2.5 text-error hover:bg-error/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log Out
                    </button>
                </li>
            </ul>
        </div>
    </div>

</aside>
