<?php

arch('application has no debug calls')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r']);

arch('models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn(['App', 'Database']);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller')
    ->not->toUse('Illuminate\Database\Eloquent\Model');

arch('livewire components')
    ->expect('App\Livewire')
    ->toExtend('Livewire\Component');

arch('providers')
    ->expect('App\Providers')
    ->toExtend('Illuminate\Support\ServiceProvider');

arch('strict types')
    ->expect('App')
    ->toUseStrictTypes();
