<?php

use App\Enums\QueenStatus;
use App\Enums\VarroaMethod;
use App\Models\Hive;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

test('inspection index requires authentication', function () {
    $this->get('/inspections')->assertRedirect('/login');
});

test('inspection index shows only the authenticated user\'s inspections', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $hive = Hive::factory()->for($user)->create();
    $otherHive = Hive::factory()->for($other)->create();

    Inspection::factory()->for($hive)->for($user, 'user')->create(['raw_notes' => 'My inspection notes']);
    Inspection::factory()->for($otherHive)->for($other, 'user')->create(['raw_notes' => 'Their inspection notes']);

    $this->actingAs($user);

    Volt::test('pages.inspections.index')
        ->assertSee('My inspection notes')
        ->assertDontSee('Their inspection notes');
});

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

test('inspection create requires authentication', function () {
    $this->get('/inspections/create')->assertRedirect('/login');
});

test('can create an inspection with only required fields', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', (string) $hive->id)
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', 'Hive looks healthy. Queen spotted on frame 3.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('inspections', [
        'hive_id' => $hive->id,
        'user_id' => $user->id,
        'raw_notes' => 'Hive looks healthy. Queen spotted on frame 3.',
    ]);
});

test('inspection raw notes are required', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', (string) $hive->id)
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', '')
        ->call('save')
        ->assertHasErrors(['rawNotes' => 'required']);
});

test('inspection hive is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', '')
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', 'Some notes')
        ->call('save')
        ->assertHasErrors(['hiveId' => 'required']);
});

test('cannot create inspection for another user\'s hive', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $otherHive = Hive::factory()->for($other)->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', (string) $otherHive->id)
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', 'Sneaky notes')
        ->call('save')
        ->assertHasErrors(['hiveId']);

    $this->assertDatabaseMissing('inspections', ['hive_id' => $otherHive->id]);
});

test('inspection is always created for the authenticated user', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', (string) $hive->id)
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', 'Notes')
        ->call('save');

    $this->assertDatabaseHas('inspections', ['user_id' => $user->id]);
});

test('scores must be between 1 and 5', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.create')
        ->set('hiveId', (string) $hive->id)
        ->set('inspectedAt', '2026-04-01T10:00')
        ->set('rawNotes', 'Notes')
        ->set('overallHealthScore', '9')
        ->call('save')
        ->assertHasErrors(['overallHealthScore']);
});

// ---------------------------------------------------------------------------
// Edit
// ---------------------------------------------------------------------------

test('inspection edit requires authentication', function () {
    $inspection = Inspection::factory()->create();

    $this->get("/inspections/{$inspection->id}/edit")->assertRedirect('/login');
});

test('can edit own inspection', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')
        ->create(['raw_notes' => 'Old notes']);

    $this->actingAs($user);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->set('rawNotes', 'Updated notes')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/inspections');

    $this->assertDatabaseHas('inspections', [
        'id' => $inspection->id,
        'raw_notes' => 'Updated notes',
    ]);
});

test('editing another user\'s inspection returns 403', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $otherHive = Hive::factory()->for($other)->create();
    $inspection = Inspection::factory()->for($otherHive)->for($other, 'user')->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->assertForbidden();
});

test('followup_questions are not overwritten on edit', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')
        ->create(['followup_questions' => ['Did you see the queen?']]);

    $this->actingAs($user);

    Volt::test('pages.inspections.edit', ['inspection' => $inspection])
        ->set('rawNotes', 'Updated notes')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('inspections', [
        'id' => $inspection->id,
        'followup_questions' => json_encode(['Did you see the queen?']),
    ]);
});

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

test('can delete own inspection', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();
    $inspection = Inspection::factory()->for($hive)->for($user, 'user')->create();

    $this->actingAs($user);

    Volt::test('pages.inspections.index')
        ->call('delete', $inspection->id);

    $this->assertDatabaseMissing('inspections', ['id' => $inspection->id]);
});

test('cannot delete another user\'s inspection', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $otherHive = Hive::factory()->for($other)->create();
    $inspection = Inspection::factory()->for($otherHive)->for($other, 'user')->create();

    $this->actingAs($user);

    expect(fn () => Volt::test('pages.inspections.index')->call('delete', $inspection->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('inspections', ['id' => $inspection->id]);
});

// ---------------------------------------------------------------------------
// Enum labels
// ---------------------------------------------------------------------------

test('QueenStatus has correct labels', function () {
    expect(QueenStatus::Laying->label())->toBe('Laying')
        ->and(QueenStatus::NotLaying->label())->toBe('Not Laying')
        ->and(QueenStatus::SwarmCells->label())->toBe('Swarm Cells')
        ->and(QueenStatus::SupersedureCells->label())->toBe('Supersedure Cells');
});

test('VarroaMethod has correct labels', function () {
    expect(VarroaMethod::SugarRoll->label())->toBe('Sugar Roll')
        ->and(VarroaMethod::AlcoholWash->label())->toBe('Alcohol Wash')
        ->and(VarroaMethod::StickyBoard->label())->toBe('Sticky Board');
});
