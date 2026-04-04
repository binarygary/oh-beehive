# Testing

## Framework & configuration

- **Pest PHP** — functional test syntax (`test()`, `it()`, `expect()`)
- **PHPUnit** under the hood — configured via `phpunit.xml`
- **In-memory SQLite** for tests (fast, isolated)
- **Livewire Volt testing** via `Livewire\Volt\Volt::test()`

## Test suites (phpunit.xml)

| Suite | Location | What it covers |
|-------|----------|----------------|
| `Unit` | `tests/Unit/` | Pure logic, no framework |
| `Feature` | `tests/Feature/` | Full HTTP + Livewire component tests |
| `Architecture` | `tests/Architecture/` | Pest arch rules (structural enforcement) |

## Running tests

```bash
composer test          # php artisan config:clear + ./vendor/bin/pest
./vendor/bin/pest      # run directly
./vendor/bin/pest --filter "hive index" # single test
```

GrumPHP runs the full suite on every `git commit` via `scripts/pest-run.sh`.

## Architecture tests (`tests/Architecture/ArchTest.php`)

Pest arch rules enforcing structural constraints:

```php
arch('strict types')->expect('App')->toUseStrictTypes();
arch('models')->expect('App\Models')->toExtend('Illuminate\Database\Eloquent\Model')->toOnlyBeUsedIn(['App', 'Database']);
arch('controllers')->expect('App\Http\Controllers')->toExtend('App\Http\Controllers\Controller')->not->toUse('Illuminate\Database\Eloquent\Model');
arch('livewire forms')->expect('App\Livewire\Forms')->toExtend('Livewire\Form');
arch('no debug calls')->expect('App')->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r']);
```

## Feature test patterns

### Authentication guard
```php
test('hive index requires authentication', function () {
    $this->get('/hives')->assertRedirect('/login');
});
```

### Volt component testing
```php
$this->actingAs($user);
Volt::test('pages.hives.create')
    ->set('name', 'My Hive')
    ->call('save')
    ->assertHasNoErrors()
    ->assertRedirect('/hives');
```

### Database assertions
```php
$this->assertDatabaseHas('hives', ['user_id' => $user->id, 'name' => 'My Hive']);
$this->assertDatabaseMissing('hives', ['id' => $hive->id]);
```

### Multi-user isolation
```php
// Tests verify users can only see/modify their own data
Volt::test('pages.hives.index')
    ->assertSee('My Hive')
    ->assertDontSee('Their Hive');
```

### Authorization (403 / 404)
```php
// Edit another user's resource → 403
Volt::test('pages.hives.edit', ['hive' => $otherHive])
    ->assertForbidden();

// Delete another user's resource → ModelNotFoundException (findOrFail scoped)
expect(fn () => Volt::test('pages.hives.index')->call('delete', $otherHive->id))
    ->toThrow(ModelNotFoundException::class);
```

## Factories

| Factory | File |
|---------|------|
| `UserFactory` | `database/factories/UserFactory.php` |
| `HiveFactory` | `database/factories/HiveFactory.php` |
| `InspectionFactory` | `database/factories/InspectionFactory.php` |

### Factory usage patterns
```php
$user = User::factory()->create();
$hive = Hive::factory()->for($user)->create();
$inspection = Inspection::factory()->for($hive)->for($user, 'user')->create();
```

## Coverage gaps

- **`InspectionParserService`** — no unit tests; AI parsing logic is completely untested
- **`ParseInspectionNotes` job** — no tests (job is never dispatched anyway)
- **Enum helpers** (`label()`, `badgeClass()`) — tested inline in feature tests, not isolated
- No contract/integration tests for OpenAI API responses
