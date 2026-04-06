<?php

declare(strict_types=1);

use App\Models\Hive;
use App\Models\Inspection;
use App\Models\User;
use App\Services\InspectionParserService;
use Livewire\Volt\Volt;
use Tests\Unit\FakeInspectionParserService;

it('tracks ai-filled field provenance after parsing on the edit form', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create([
        'weather' => 'Overcast',
        'queen_status' => 'not_laying',
    ]);

    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_seen' => true,
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'disease_observations' => ['Chalkbrood'],
        'followup_questions' => ['How many frames of bees?'],
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->call('parse')
        ->assertSet('weather', 'Sunny and warm')
        ->assertSet('aiFilledFields', [
            'queenSeen' => true,
            'queenStatus' => true,
            'weather' => true,
            'diseaseObservations' => true,
        ]);
});

it('clears provenance only for the field a keeper overrides on the edit form', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create([
        'weather' => 'Overcast',
        'queen_status' => 'not_laying',
    ]);

    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_seen' => true,
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->call('parse')
        ->set('queenStatus', 'not_laying')
        ->assertSet('aiFilledFields', [
            'queenSeen' => true,
            'weather' => true,
        ]);
});

it('refreshes edit-form provenance from the latest parse payload only', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create([
        'weather' => 'Overcast',
        'queen_status' => 'not_laying',
        'frames_of_bees' => 7,
    ]);

    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_seen' => true,
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'disease_observations' => ['Chalkbrood'],
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    $component = Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->call('parse')
        ->assertSet('aiFilledFields', [
            'queenSeen' => true,
            'queenStatus' => true,
            'weather' => true,
            'diseaseObservations' => true,
        ]);

    $fakeParser->parsedData = [
        'weather' => 'Cloudy',
        'followup_questions' => null,
    ];

    $component
        ->call('parse')
        ->assertSet('queenStatus', 'laying')
        ->assertSet('weather', 'Cloudy')
        ->assertSet('framesOfBees', '7')
        ->assertSet('aiFilledFields', [
            'weather' => true,
        ]);
});

it('renders ai badges for parsed edit-form labels', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create();

    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->call('parse')
        ->assertSeeHtml('badge badge-sm badge-primary')
        ->assertSee('AI');
});

it('does not mark omitted parser fields as ai-filled on the edit form', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create([
        'queen_status' => 'not_laying',
        'weather' => 'Overcast',
    ]);

    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'weather' => 'Cloudy',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->call('parse')
        ->assertSet('queenStatus', 'not_laying')
        ->assertSet('aiFilledFields', [
            'weather' => true,
        ]);
});
