<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\InspectionParserService;
use Livewire\Volt\Volt;
use Tests\Unit\FakeInspectionParserService;

it('tracks ai-filled field provenance after parsing on the create form', function () {
    $user = User::factory()->create();
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

    Volt::test('pages.inspections.create')
        ->set('rawNotes', 'Queen is present, laying, sunny weather, and some chalkbrood seen.')
        ->call('parse')
        ->assertSet('aiFilledFields', [
            'queenSeen' => true,
            'queenStatus' => true,
            'weather' => true,
            'diseaseObservations' => true,
        ]);
});

it('clears provenance only for the field a keeper overrides on the create form', function () {
    $user = User::factory()->create();
    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_seen' => true,
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.create')
        ->set('rawNotes', 'Queen is laying in sunny weather.')
        ->call('parse')
        ->set('queenStatus', 'not_laying')
        ->assertSet('aiFilledFields', [
            'queenSeen' => true,
            'weather' => true,
        ]);
});

it('refreshes create-form provenance from the latest parse payload only', function () {
    $user = User::factory()->create();
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

    $component = Volt::test('pages.inspections.create')
        ->set('rawNotes', 'First parse payload.')
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
        ->set('rawNotes', 'Second parse payload.')
        ->call('parse')
        ->assertSet('aiFilledFields', [
            'weather' => true,
        ]);
});

it('renders ai badges for parsed create-form labels', function () {
    $user = User::factory()->create();
    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'queen_status' => 'laying',
        'weather' => 'Sunny and warm',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.create')
        ->set('rawNotes', 'Queen is laying in sunny weather.')
        ->call('parse')
        ->assertSeeHtml('badge badge-sm badge-primary')
        ->assertSee('AI');
});

it('does not mark omitted parser fields as ai-filled on the create form', function () {
    $user = User::factory()->create();
    $fakeParser = new FakeInspectionParserService;
    $fakeParser->parsedData = [
        'weather' => 'Cloudy',
        'followup_questions' => null,
    ];

    $this->actingAs($user);
    $this->app->instance(InspectionParserService::class, $fakeParser);

    Volt::test('pages.inspections.create')
        ->set('rawNotes', 'Cloudy weather only.')
        ->call('parse')
        ->assertSet('aiFilledFields', [
            'weather' => true,
        ]);
});
