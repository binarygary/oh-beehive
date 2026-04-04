# Coding Conventions

**Analysis Date:** 2026-04-04

## Naming Patterns

**Files:**
- Classes use PascalCase: `HiveFactory.php`, `InspectionParserService.php`, `ParseInspectionNotes.php`
- Directories use lowercase with hyphens for multi-word: `app/Http/Controllers`, `app/Livewire/Forms`
- Enum files use PascalCase: `HiveStatus.php`, `QueenStatus.php`, `VarroaMethod.php`
- Blade templates use kebab-case: `create.blade.php`, `update-password-form.blade.php`

**Functions and Methods:**
- Use camelCase for public/private methods: `parseRaw()`, `extractFields()`, `label()`, `badgeClass()`
- Private helper methods use camelCase: `systemPrompt()`, `extractFields()`
- Lifecycle hooks (Livewire): `mount()`, `with()`, `updatedRawNotes()` (update hooks use camelCase property name)
- Factory methods use camelCase: `inactive()`, `deadOut()`

**Variables:**
- Use camelCase for local and property variables: `$rawNotes`, `$hiveId`, `$inspectedAt`, `$followupQuestions`
- Boolean properties/variables often start with verb or use is/has: `$queenSeen`, `$eggsPresent`, `$feedingDone`
- Properties use snake_case in database attributes but camelCase in Livewire component properties
- Array properties indicate collection: `$followupQuestions`, `$diseaseObservations`

**Types:**
- Enums use PascalCase with PascalCase cases: `HiveStatus::Active`, `QueenStatus::Laying`
- Enum case values use snake_case: `'active'`, `'laying'`, `'not_laying'`, `'dead_out'`
- Model class names are singular PascalCase: `Hive`, `Inspection`, `User`

## Code Style

**Formatting:**
- Laravel Pint (code formatter) applied on all commits via GrumPHP
- Run `composer format` to auto-fix style issues
- Run `composer format:check` to validate formatting without changes
- All PHP files must include `declare(strict_types=1);` immediately after opening tag

**Linting:**
- Laravel Pint enforces PSR-12 with Laravel conventions
- PHPStan (Larastan) runs at level 5 on `app/` directory only
- Run `composer lint` to check static analysis
- GrumPHP runs Pint check and PHPStan on every `git commit` automatically

## Import Organization

**Order:**
1. Built-in PHP classes/namespaces
2. Laravel framework classes (`Illuminate\*`)
3. Application classes (`App\*`)
4. Database factories and seeders (`Database\*`)
5. External packages (Livewire, OpenAI, etc.)

**Path Aliases:**
- No custom path aliases configured; use full namespace imports
- Standard Laravel structure: `use App\Models\`, `use App\Enums\`, `use App\Services\`, etc.

**Example from `InspectionParserService.php`:**
```php
use App\Models\Inspection;
use OpenAI\Client;
```

## Error Handling

**Patterns:**
- Service classes catch `\Throwable` for unexpected failures and return safe defaults (empty arrays): `catch (\Throwable) { return []; }`
- Validation errors use Laravel's standard validation rules and are handled by Livewire form validation
- Database query failures (like `ModelNotFoundException`) are allowed to propagate in tests and are tested explicitly
- Livewire components silently return on API call failures with early returns: `if (strlen(...) < 15) { return; }`

## Logging

**Framework:** Not explicitly configured; uses Laravel's default logging

**Patterns:**
- No explicit logging observed in current codebase
- Follow Laravel default logging for error tracking

## Comments

**When to Comment:**
- JSDoc/PHPStan comments on properties and return types are mandatory: `/** @property Carbon|null $acquired_at */`
- Docblocks on public methods with complex logic
- Docblocks on return types that need generic type hints: `/** @return array<string, mixed> */`
- Comments explaining business logic, not obvious code

**JSDoc/TSDoc:**
- All properties need PHPDoc with `@property` and type hints, especially enums and carbon dates
- All methods with complex return types need `@return` docblocks with generic types
- Factory docblocks declare generics: `/** @extends Factory<Hive> */`
- Livewire form properties use `@var` docblocks when type is array: `/** @var array<int, string> */`

**Example from `Hive.php`:**
```php
/**
 * @property Carbon|null $acquired_at
 * @property HiveStatus $status
 */
#[Fillable(['user_id', 'name', 'location', 'acquired_at', 'status', 'notes'])]
class Hive extends Model
```

## Function Design

**Size:**
- Methods typically 10-30 lines; rarely exceed 50 lines
- Extract complex logic into private helper methods
- Avoid deeply nested conditions; use early returns

**Parameters:**
- Use readonly constructor promotion for dependency injection: `public function __construct(private readonly Client $client) {}`
- Limit parameters to 3-4; use arrays for larger data sets
- Type-hint all parameters and return types (strict types enforced)

**Return Values:**
- Be explicit about nullable returns with `|null` type hints
- Return empty arrays `[]` instead of null for collection operations
- Services return arrays; models return Eloquent results
- Void return when side effects only

**Example from `ParseInspectionNotes.php`:**
```php
public function __construct(private readonly Inspection $inspection) {}

public function handle(InspectionParserService $parser): void
{
    $parser->parse($this->inspection);
}
```

## Module Design

**Exports:**
- Models export relationships via methods returning `BelongsTo`, `HasMany`
- Services export public methods that perform single responsibilities
- Enums export `label()` and format helper methods for UI display
- Jobs implement `ShouldQueue` interface explicitly

**Barrel Files:**
- Not used; no barrel/index.php files for re-exports
- Import specific classes directly

**Example Exports:**
- `Hive` model exports: `user()` relationship, `inspections()` relationship
- `InspectionParserService` exports: `parseRaw(string): array`, `parse(Inspection): void`
- `HiveStatus` enum exports: `label(): string`, `badgeClass(): string`

## Strict Types

**Mandatory:**
- All files in `app/` must declare `declare(strict_types=1);` as first statement after `<?php`
- Enforced by architecture test `tests/Architecture/ArchTest.php`
- No exceptions; any new file must include this declaration

---

*Convention analysis: 2026-04-04*
