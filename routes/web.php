<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/hives', 'pages.hives.index')->name('hives.index');
    Volt::route('/hives/create', 'pages.hives.create')->name('hives.create');
    Volt::route('/hives/{hive}/edit', 'pages.hives.edit')->name('hives.edit');
});

require __DIR__.'/auth.php';
