# Oh Beehive — Claude Context

## What this is
A Laravel app for recording beehive inspections with AI assistance. The keeper types free-text inspection notes; the AI parses them into structured fields and asks follow-up questions for anything it couldn't resolve.

**Future enhancement:** Live dictation with real-time field checkmarks as AI detects them.

## Stack
- Laravel 13, PHP 8.3
- Livewire v3 + Volt (Breeze scaffolded — Breeze downgraded Livewire from v4 to v3)
- Tailwind CSS v4 via `@tailwindcss/vite` (no `tailwind.config.js` — config lives in `app.css`)
- DaisyUI v5 + `@tailwindcss/forms` loaded via `@plugin` in `resources/css/app.css`
- SQLite (dev + in-memory for tests)

## Key decisions
- **No registration route** — users created only via `php artisan make:user`
- **Multi-user** — every hive and inspection has a `user_id` FK
- **All Langstroth** — no hive type field
- **Treatment is free text** — not structured
- **AI flow (round 1):** textarea → OpenAI parses `raw_notes` → fills structured fields → follow-up questions for unresolved fields stored in `followup_questions` JSON column

## Composer scripts
```bash
composer test          # clear config + run pest
composer lint          # larastan
composer format        # pint (auto-fix)
composer format:check  # pint (check only)
composer dev           # all dev servers (Laravel + queue + pail + Vite)
```

## Toolchain
GrumPHP runs automatically on every `git commit`:
1. Larastan (PHPStan level 5, `phpstan.neon`, `app/` only)
2. `scripts/pint-check.sh` — Pint in check mode
3. `scripts/pest-run.sh` — full Pest suite

Test suites: `Unit`, `Feature`, `Architecture` (defined in `phpunit.xml`).

## Rules every new file must follow
- **`declare(strict_types=1);`** after `<?php` in every `app/` file — enforced by arch test
- Livewire components extend `Livewire\Component`
- Livewire form objects extend `Livewire\Form`
- Controllers extend `App\Http\Controllers\Controller`, no direct Eloquent use
- Models extend `Illuminate\Database\Eloquent\Model`, only used within `App\` and `Database\`
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()` in `App\`

## Domain models

### Hive
`user_id`, `name`, `location` (nullable), `acquired_at` (nullable date), `status` (HiveStatus enum: active/inactive/dead_out), `notes` (nullable text)

### Inspection
Belongs to `Hive` + `User`. Key fields:
- `raw_notes` — the keeper's typed observations (AI input)
- `followup_questions` — JSON array of questions AI couldn't resolve
- Numeric scores 1–5: `brood_pattern_score`, `honey_stores_score`, `temperament_score`, `overall_health_score`
- Booleans: `queen_seen`, `eggs_present`, `larvae_present`, `capped_brood_present`, `feeding_done`
- Counts: `frames_of_brood`, `frames_of_bees`, `frames_of_honey`
- `queen_status` enum: laying / not_laying / swarm_cells / supersedure_cells
- `varroa_count` (per 100 bees), `varroa_method` enum — optional, used a few times/year
- `disease_observations` (JSON array of strings)
- `treatment_applied` (free text)

## What's next
1. Basic UI shell — DaisyUI nav/layout, dashboard
2. Hive CRUD — list, create, edit, delete
3. Inspection form — raw_notes textarea + AI parsing + follow-up questions
4. AI integration — OpenAI API parses `raw_notes` into structured inspection fields

<!-- GSD:project-start source:PROJECT.md -->
## Project

**Oh Beehive**

A personal beekeeping inspection app that turns free-form notes (typed or recorded audio) into structured inspection records using AI. The keeper types or speaks their observations; the AI parses them into structured fields, asks follow-up questions for anything it couldn't resolve, and the keeper reviews and confirms before saving. May be shared publicly for free.

**Core Value:** A beekeeper can record an inspection in natural language and get a complete, structured record without manually filling in fields.

### Constraints

- **Tech stack:** Laravel 13 + Livewire v3 + Volt — no framework changes
- **Database:** SQLite for dev/personal use; not designed for scale
- **AI provider:** OpenAI (gpt-4o-mini for parsing, Whisper for audio transcription)
- **No registration:** Users created only via `php artisan make:user`
- **Code quality:** GrumPHP enforces Larastan level 5 + Pint + Pest on every commit; `declare(strict_types=1)` required in all `app/` files
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP 8.3+ - Backend application logic, models, services, controllers
- JavaScript (ES modules) - Frontend interactivity via Livewire components
- HTML (Blade templates) - View rendering via Laravel Blade + Livewire
## Runtime
- PHP 8.3+ (CLI and built-in server via `php artisan serve`)
- Node.js (required for npm package management and Vite build process)
- Composer 2.x - PHP dependency management
- npm - JavaScript dependency management
## Frameworks
- Laravel 13.0+ - Full-stack web framework
- Livewire v3.6.4+ - Real-time reactive components without writing JavaScript
- Livewire Volt v1.7.0+ - Single-file Livewire component syntax
- Tailwind CSS v4.2.2 - Utility-first CSS framework via `@tailwindcss/vite`
- DaisyUI v5.5.19 - Tailwind component library loaded via `@plugin "daisyui"`
- `@tailwindcss/forms` v0.5.11 - Form input component styles
- Laravel Breeze v2.4 (downgraded scaffolding) - Initial auth UI shell
- Vite v8.0.0+ - Fast module bundler for dev server and production builds
- concurrently v9.0.1 - Run multiple dev servers in parallel
- Pest v4.4+ - PHP testing framework
- Pest Laravel Plugin v4.1+ - Laravel-specific testing utilities
- Pest Architecture Plugin v4.0+ - Architecture testing via Pest
- Mockery v1.6+ - Mocking library for tests
- FakerPHP v1.23+ - Test data generation
- PHPStan (Larastan) v3.9+ - Static type analysis at level 5
- Laravel Pint v1.27+ - PHP code formatter and auto-fixer
- GrumPHP v2.19+ - Git pre-commit hook runner
- Laravel Tinker v3.0+ - Interactive REPL for Laravel
- Laravel Pail v1.2.5+ - Real-time log streaming
## Key Dependencies
- `openai-php/client` v0.19.1+ - OpenAI API client for inspection parsing
- `laravel/framework` core packages handle:
## Configuration
- `.env` file (not committed; use `.env.example` as template)
- Key vars configured:
- `vite.config.js` - Vite bundler configuration
- No `tailwind.config.js` - Tailwind v4 config via `@theme` in `resources/css/app.css`
- `phpstan.neon` - PHPStan static analysis rules
- `composer.json` - PHP scripts:
- `scripts/` directory contains helper scripts:
## Platform Requirements
- PHP 8.3+ (CLI and built-in server)
- Node.js (for npm packages and Vite)
- Composer 2.x
- Git (GrumPHP hooks on commit)
- SQLite (default; file at `database/database.sqlite`)
- PHP 8.3+ with required extensions (PDO SQLite or MySQL, JSON, etc.)
- Web server (Apache, Nginx, or PHP built-in)
- Optional: Redis for session/cache optimization
- Optional: AWS S3 for file storage (configured but not required)
- Optional: Mail service (Postmark, Resend, SendMail, AWS SES)
- No version pinning file (`.nvmrc` not present)
- Uses npm from PATH
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Naming Patterns
- Classes use PascalCase: `HiveFactory.php`, `InspectionParserService.php`, `ParseInspectionNotes.php`
- Directories use lowercase with hyphens for multi-word: `app/Http/Controllers`, `app/Livewire/Forms`
- Enum files use PascalCase: `HiveStatus.php`, `QueenStatus.php`, `VarroaMethod.php`
- Blade templates use kebab-case: `create.blade.php`, `update-password-form.blade.php`
- Use camelCase for public/private methods: `parseRaw()`, `extractFields()`, `label()`, `badgeClass()`
- Private helper methods use camelCase: `systemPrompt()`, `extractFields()`
- Lifecycle hooks (Livewire): `mount()`, `with()`, `updatedRawNotes()` (update hooks use camelCase property name)
- Factory methods use camelCase: `inactive()`, `deadOut()`
- Use camelCase for local and property variables: `$rawNotes`, `$hiveId`, `$inspectedAt`, `$followupQuestions`
- Boolean properties/variables often start with verb or use is/has: `$queenSeen`, `$eggsPresent`, `$feedingDone`
- Properties use snake_case in database attributes but camelCase in Livewire component properties
- Array properties indicate collection: `$followupQuestions`, `$diseaseObservations`
- Enums use PascalCase with PascalCase cases: `HiveStatus::Active`, `QueenStatus::Laying`
- Enum case values use snake_case: `'active'`, `'laying'`, `'not_laying'`, `'dead_out'`
- Model class names are singular PascalCase: `Hive`, `Inspection`, `User`
## Code Style
- Laravel Pint (code formatter) applied on all commits via GrumPHP
- Run `composer format` to auto-fix style issues
- Run `composer format:check` to validate formatting without changes
- All PHP files must include `declare(strict_types=1);` immediately after opening tag
- Laravel Pint enforces PSR-12 with Laravel conventions
- PHPStan (Larastan) runs at level 5 on `app/` directory only
- Run `composer lint` to check static analysis
- GrumPHP runs Pint check and PHPStan on every `git commit` automatically
## Import Organization
- No custom path aliases configured; use full namespace imports
- Standard Laravel structure: `use App\Models\`, `use App\Enums\`, `use App\Services\`, etc.
## Error Handling
- Service classes catch `\Throwable` for unexpected failures and return safe defaults (empty arrays): `catch (\Throwable) { return []; }`
- Validation errors use Laravel's standard validation rules and are handled by Livewire form validation
- Database query failures (like `ModelNotFoundException`) are allowed to propagate in tests and are tested explicitly
- Livewire components silently return on API call failures with early returns: `if (strlen(...) < 15) { return; }`
## Logging
- No explicit logging observed in current codebase
- Follow Laravel default logging for error tracking
## Comments
- JSDoc/PHPStan comments on properties and return types are mandatory: `/** @property Carbon|null $acquired_at */`
- Docblocks on public methods with complex logic
- Docblocks on return types that need generic type hints: `/** @return array<string, mixed> */`
- Comments explaining business logic, not obvious code
- All properties need PHPDoc with `@property` and type hints, especially enums and carbon dates
- All methods with complex return types need `@return` docblocks with generic types
- Factory docblocks declare generics: `/** @extends Factory<Hive> */`
- Livewire form properties use `@var` docblocks when type is array: `/** @var array<int, string> */`
#[Fillable(['user_id', 'name', 'location', 'acquired_at', 'status', 'notes'])]
## Function Design
- Methods typically 10-30 lines; rarely exceed 50 lines
- Extract complex logic into private helper methods
- Avoid deeply nested conditions; use early returns
- Use readonly constructor promotion for dependency injection: `public function __construct(private readonly Client $client) {}`
- Limit parameters to 3-4; use arrays for larger data sets
- Type-hint all parameters and return types (strict types enforced)
- Be explicit about nullable returns with `|null` type hints
- Return empty arrays `[]` instead of null for collection operations
- Services return arrays; models return Eloquent results
- Void return when side effects only
## Module Design
- Models export relationships via methods returning `BelongsTo`, `HasMany`
- Services export public methods that perform single responsibilities
- Enums export `label()` and format helper methods for UI display
- Jobs implement `ShouldQueue` interface explicitly
- Not used; no barrel/index.php files for re-exports
- Import specific classes directly
- `Hive` model exports: `user()` relationship, `inspections()` relationship
- `InspectionParserService` exports: `parseRaw(string): array`, `parse(Inspection): void`
- `HiveStatus` enum exports: `label(): string`, `badgeClass(): string`
## Strict Types
- All files in `app/` must declare `declare(strict_types=1);` as first statement after `<?php`
- Enforced by architecture test `tests/Architecture/ArchTest.php`
- No exceptions; any new file must include this declaration
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Multi-user single-tenant design with per-user hive and inspection data isolation
- Real-time AI parsing of inspection notes via OpenAI API
- Livewire Volt for single-file component syntax
- Authentication-first routing (verified email required for core features)
- Queueable job processing for async AI parsing
## Layers
- Purpose: Data representation and relationships using Eloquent ORM
- Location: `app/Models/`
- Contains: `User`, `Hive`, `Inspection` models with type-safe relationships
- Depends on: Database, Enums for casting
- Used by: Services, Livewire components, Controllers
- Pattern: Each model has explicit relationship definitions with PHPDoc types
- Purpose: Business logic and external API integration
- Location: `app/Services/`
- Contains: `InspectionParserService` - coordinates OpenAI parsing, field extraction, and validation
- Depends on: Models, OpenAI Client
- Used by: Livewire components, Queue jobs
- Pattern: Service methods return typed arrays or void; coordinate multiple concerns
- Purpose: Interactive UI with real-time state management and validation
- Location: `resources/views/livewire/pages/`
- Contains: Volt components combining PHP logic and Blade template in single file
- Depends on: Models, Services, Enums, Tailwind/DaisyUI
- Used by: Web routes
- Pattern: Component state synced to Livewire properties; form submission triggers validation and persistence
- Purpose: Request handling and background job dispatch
- Location: `routes/web.php`, `app/Jobs/`
- Contains: Web routes for Livewire, queue job for async AI parsing
- Depends on: Models, Services
- Used by: Web server, queue worker
- Pattern: Routes guard with auth middleware; jobs use dependency injection
- Purpose: Configuration and service registration
- Location: `app/Providers/`, `bootstrap/app.php`, `config/`
- Contains: AppServiceProvider registers OpenAI client singleton
- Depends on: External services (OpenAI)
- Used by: Laravel bootstrap
- Pattern: Configuration-driven, environment-based secrets
## Data Flow
- Component properties are Livewire-reactive: two-way binding with form inputs
- Validation happens server-side in `save()` method
- Errors returned to component and displayed inline via `@error` blade directives
- All state scoped to authenticated user via `auth()->user()` guards
- No global state; each component instance owns its data
## Key Abstractions
- Purpose: Captures both raw keeper observations and structured AI-extracted fields
- Examples: `app/Models/Inspection.php`, `app/Services/InspectionParserService.php`
- Pattern: `raw_notes` is immutable AI input; AI output fields can be edited by user; `followup_questions` tracks ambiguities
- Purpose: Ensures users see only their own hives/inspections
- Examples: `app/Models/User.php` with `hives()` and `inspections()` relationships
- Pattern: All queries use `auth()->user()->hives()` to filter by user_id foreign key
- Purpose: Type-safe representation of restricted values (queen status, varroa method, hive status)
- Examples: `app/Enums/HiveStatus.php`, `app/Enums/QueenStatus.php`, `app/Enums/VarroaMethod.php`
- Pattern: Each enum has `label()` for display and `badgeClass()` for UI styling; Eloquent auto-casts enum properties
- Purpose: Bridge reactive UI properties to structured Eloquent create/update
- Examples: `resources/views/livewire/pages/inspections/create.blade.php`
- Pattern: Component property names match snake_case database columns via lambda converters (`$nb`, `$ni`, `$ns`)
## Entry Points
- Location: `/` → `routes/web.php` → `Route::view('/', 'welcome')`
- Triggers: HTTP GET /
- Responsibilities: Shows unauthenticated landing page
- Location: `/dashboard` → `resources/views/dashboard.blade.php`
- Triggers: HTTP GET /dashboard (requires auth + verified email)
- Responsibilities: Shows authenticated user overview
- Location: `/hives`, `/hives/create`, `/hives/{hive}/edit`
- Triggers: Livewire route dispatching via `Volt::route()`
- Responsibilities: CRUD operations on hives; list, create, edit, delete
- Location: `/inspections`, `/inspections/create`, `/inspections/{inspection}/edit`
- Triggers: Livewire route dispatching via `Volt::route()`
- Responsibilities: Record inspections with AI-assisted parsing
- Location: `app/Jobs/ParseInspectionNotes.php`
- Triggers: Job dispatch (currently not used; parsing is synchronous in component)
- Responsibilities: Would handle async AI parsing if needed
## Error Handling
- **OpenAI Integration:** `updatedRawNotes()` wraps `$parser->parseRaw()` in try-catch; silently returns on exception (graceful degradation)
- **Model Validation:** `save()` method calls `$this->validate()` with Rules including enum checks, integer bounds (1–5 for scores, 0+ for counts)
- **Ownership Verification:** Livewire `hiveId` validation uses `Rule::exists('hives', 'id')->where('user_id', auth()->id())` to prevent cross-user access
- **Null Field Handling:** Service `extractFields()` checks `array_key_exists()` before accessing parsed data; invalid diseases filtered against allowlist
## Cross-Cutting Concerns
<!-- GSD:architecture-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd:quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd:debug` for investigation and bug fixing
- `/gsd:execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd:profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
