<?php

use App\Enums\HiveStatus;
use App\Models\Hive;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

test('hive index requires authentication', function () {
    $this->get('/hives')->assertRedirect('/login');
});

test('hive index shows only the authenticated user\'s hives', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Hive::factory()->for($user)->create(['name' => 'My Hive']);
    Hive::factory()->for($other)->create(['name' => 'Their Hive']);

    $this->actingAs($user);

    Volt::test('pages.hives.index')
        ->assertSee('My Hive')
        ->assertDontSee('Their Hive');
});

test('hive index shows empty state when user has no hives', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.hives.index')
        ->assertSee('No hives yet');
});

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

test('hive create requires authentication', function () {
    $this->get('/hives/create')->assertRedirect('/login');
});

test('can create a hive with valid data', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.hives.create')
        ->set('name', 'North Meadow #1')
        ->set('location', 'Back garden')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/hives');

    $this->assertDatabaseHas('hives', [
        'user_id' => $user->id,
        'name' => 'North Meadow #1',
        'location' => 'Back garden',
        'status' => 'active',
    ]);
});

test('hive name is required on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.hives.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('hive status must be a valid enum value on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.hives.create')
        ->set('name', 'Test Hive')
        ->set('status', 'invalid')
        ->call('save')
        ->assertHasErrors(['status']);
});

test('hive is created for the authenticated user regardless of input', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages.hives.create')
        ->set('name', 'Sneaky Hive')
        ->call('save');

    $this->assertDatabaseHas('hives', ['user_id' => $user->id, 'name' => 'Sneaky Hive']);
    $this->assertDatabaseMissing('hives', ['user_id' => $other->id, 'name' => 'Sneaky Hive']);
});

// ---------------------------------------------------------------------------
// Edit
// ---------------------------------------------------------------------------

test('hive edit requires authentication', function () {
    $hive = Hive::factory()->create();

    $this->get("/hives/{$hive->id}/edit")->assertRedirect('/login');
});

test('can edit own hive', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user);

    Volt::test('pages.hives.edit', ['hive' => $hive])
        ->set('name', 'New Name')
        ->set('status', 'inactive')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/hives');

    $this->assertDatabaseHas('hives', [
        'id' => $hive->id,
        'name' => 'New Name',
        'status' => 'inactive',
    ]);
});

test('hive name is required on edit', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.hives.edit', ['hive' => $hive])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('editing another user\'s hive returns 403', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $hive = Hive::factory()->for($other)->create();

    $this->actingAs($user);

    Volt::test('pages.hives.edit', ['hive' => $hive])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

test('can delete own hive', function () {
    $user = User::factory()->create();
    $hive = Hive::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('pages.hives.index')
        ->call('delete', $hive->id);

    $this->assertDatabaseMissing('hives', ['id' => $hive->id]);
});

test('cannot delete another user\'s hive', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $hive = Hive::factory()->for($other)->create();

    $this->actingAs($user);

    // findOrFail scoped to user's hives throws 404 — hive is never deleted
    expect(fn () => Volt::test('pages.hives.index')->call('delete', $hive->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('hives', ['id' => $hive->id]);
});

// ---------------------------------------------------------------------------
// Status enum helpers
// ---------------------------------------------------------------------------

test('HiveStatus has correct labels', function () {
    expect(HiveStatus::Active->label())->toBe('Active')
        ->and(HiveStatus::Inactive->label())->toBe('Inactive')
        ->and(HiveStatus::DeadOut->label())->toBe('Dead Out');
});

test('HiveStatus has correct badge classes', function () {
    expect(HiveStatus::Active->badgeClass())->toBe('badge-success')
        ->and(HiveStatus::Inactive->badgeClass())->toBe('badge-neutral')
        ->and(HiveStatus::DeadOut->badgeClass())->toBe('badge-error');
});
