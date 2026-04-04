# Architecture

**Analysis Date:** 2026-04-04

## Pattern Overview

**Overall:** Monolithic Laravel application with Livewire v3 for interactive UI components. Three-layer architecture: Models (persistence), Services (business logic), and Livewire Components (presentation).

**Key Characteristics:**
- Multi-user single-tenant design with per-user hive and inspection data isolation
- Real-time AI parsing of inspection notes via OpenAI API
- Livewire Volt for single-file component syntax
- Authentication-first routing (verified email required for core features)
- Queueable job processing for async AI parsing

## Layers

**Model Layer:**
- Purpose: Data representation and relationships using Eloquent ORM
- Location: `app/Models/`
- Contains: `User`, `Hive`, `Inspection` models with type-safe relationships
- Depends on: Database, Enums for casting
- Used by: Services, Livewire components, Controllers
- Pattern: Each model has explicit relationship definitions with PHPDoc types

**Service Layer:**
- Purpose: Business logic and external API integration
- Location: `app/Services/`
- Contains: `InspectionParserService` - coordinates OpenAI parsing, field extraction, and validation
- Depends on: Models, OpenAI Client
- Used by: Livewire components, Queue jobs
- Pattern: Service methods return typed arrays or void; coordinate multiple concerns

**Presentation Layer (Livewire):**
- Purpose: Interactive UI with real-time state management and validation
- Location: `resources/views/livewire/pages/`
- Contains: Volt components combining PHP logic and Blade template in single file
- Depends on: Models, Services, Enums, Tailwind/DaisyUI
- Used by: Web routes
- Pattern: Component state synced to Livewire properties; form submission triggers validation and persistence

**HTTP/Queue Layer:**
- Purpose: Request handling and background job dispatch
- Location: `routes/web.php`, `app/Jobs/`
- Contains: Web routes for Livewire, queue job for async AI parsing
- Depends on: Models, Services
- Used by: Web server, queue worker
- Pattern: Routes guard with auth middleware; jobs use dependency injection

**Infrastructure Layer:**
- Purpose: Configuration and service registration
- Location: `app/Providers/`, `bootstrap/app.php`, `config/`
- Contains: AppServiceProvider registers OpenAI client singleton
- Depends on: External services (OpenAI)
- Used by: Laravel bootstrap
- Pattern: Configuration-driven, environment-based secrets

## Data Flow

**Inspection Creation Flow:**

1. User navigates to `/inspections/create` (Livewire routed)
2. Component mounts with user's hives preloaded via `with()` method
3. User types raw inspection notes into textarea
4. `updatedRawNotes()` debounced listener (5000ms) fires
5. `InspectionParserService->parseRaw()` calls OpenAI API with system prompt
6. GPT-4o-mini returns JSON with extracted fields and followup questions
7. Component state properties updated with parsed values (queen_seen → queenSeen, etc.)
8. UI shows follow-up questions alert if AI couldn't resolve details
9. User manually adjusts/fills form fields as needed
10. On form submit, `save()` validates all fields via Laravel rules
11. Inspection created via `auth()->user()->inspections()->create()`
12. Redirect to edit page

**Follow-up Resolution Flow:**

1. AI initially parses notes with ambiguities
2. Service stores follow-up questions in `$inspection->followup_questions` JSON
3. User edits form fields based on prompts
4. On save, `followup_questions` nulled if empty array
5. Next edit shows updated inspection data ready for refinement

**State Management:**

- Component properties are Livewire-reactive: two-way binding with form inputs
- Validation happens server-side in `save()` method
- Errors returned to component and displayed inline via `@error` blade directives
- All state scoped to authenticated user via `auth()->user()` guards
- No global state; each component instance owns its data

## Key Abstractions

**Inspection as AI-Augmented Model:**
- Purpose: Captures both raw keeper observations and structured AI-extracted fields
- Examples: `app/Models/Inspection.php`, `app/Services/InspectionParserService.php`
- Pattern: `raw_notes` is immutable AI input; AI output fields can be edited by user; `followup_questions` tracks ambiguities

**Hive Ownership via Multi-User Scoping:**
- Purpose: Ensures users see only their own hives/inspections
- Examples: `app/Models/User.php` with `hives()` and `inspections()` relationships
- Pattern: All queries use `auth()->user()->hives()` to filter by user_id foreign key

**Enum-Based Domain Values:**
- Purpose: Type-safe representation of restricted values (queen status, varroa method, hive status)
- Examples: `app/Enums/HiveStatus.php`, `app/Enums/QueenStatus.php`, `app/Enums/VarroaMethod.php`
- Pattern: Each enum has `label()` for display and `badgeClass()` for UI styling; Eloquent auto-casts enum properties

**Livewire Form State to Database:**
- Purpose: Bridge reactive UI properties to structured Eloquent create/update
- Examples: `resources/views/livewire/pages/inspections/create.blade.php`
- Pattern: Component property names match snake_case database columns via lambda converters (`$nb`, `$ni`, `$ns`)

## Entry Points

**Web Root:**
- Location: `/` → `routes/web.php` → `Route::view('/', 'welcome')`
- Triggers: HTTP GET /
- Responsibilities: Shows unauthenticated landing page

**Dashboard:**
- Location: `/dashboard` → `resources/views/dashboard.blade.php`
- Triggers: HTTP GET /dashboard (requires auth + verified email)
- Responsibilities: Shows authenticated user overview

**Hive Management:**
- Location: `/hives`, `/hives/create`, `/hives/{hive}/edit`
- Triggers: Livewire route dispatching via `Volt::route()`
- Responsibilities: CRUD operations on hives; list, create, edit, delete

**Inspection Management:**
- Location: `/inspections`, `/inspections/create`, `/inspections/{inspection}/edit`
- Triggers: Livewire route dispatching via `Volt::route()`
- Responsibilities: Record inspections with AI-assisted parsing

**Queue Worker:**
- Location: `app/Jobs/ParseInspectionNotes.php`
- Triggers: Job dispatch (currently not used; parsing is synchronous in component)
- Responsibilities: Would handle async AI parsing if needed

## Error Handling

**Strategy:** Try-catch for external API calls; Laravel validation rules for user input; implicit null coalescing for optional fields.

**Patterns:**

- **OpenAI Integration:** `updatedRawNotes()` wraps `$parser->parseRaw()` in try-catch; silently returns on exception (graceful degradation)
  - File: `resources/views/livewire/pages/inspections/create.blade.php` lines 76–80

- **Model Validation:** `save()` method calls `$this->validate()` with Rules including enum checks, integer bounds (1–5 for scores, 0+ for counts)
  - File: `resources/views/livewire/pages/inspections/create.blade.php` lines 117–135

- **Ownership Verification:** Livewire `hiveId` validation uses `Rule::exists('hives', 'id')->where('user_id', auth()->id())` to prevent cross-user access
  - File: `resources/views/livewire/pages/inspections/create.blade.php` line 118

- **Null Field Handling:** Service `extractFields()` checks `array_key_exists()` before accessing parsed data; invalid diseases filtered against allowlist
  - File: `app/Services/InspectionParserService.php` lines 95–135

## Cross-Cutting Concerns

**Logging:** Not explicitly configured in codebase; Laravel default logging to `storage/logs/` via config/logging.php.

**Validation:** Centred in Livewire component `validate()` calls and Eloquent model fillable guards. No global request validation middleware.

**Authentication:** All protected routes require `['auth', 'verified']` middleware. User identity available via `auth()->user()` and `auth()->id()` throughout app.

**Multi-User Isolation:** Enforced at query level via Eloquent relationship chaining (`auth()->user()->hives()`) and validation rules. No super-admin bypass patterns.

**Type Safety:** PHP 8.3 strict types enforced in all `app/` files by architecture test. Eloquent model casts and PHPDoc types ensure end-to-end type integrity.

---

*Architecture analysis: 2026-04-04*
