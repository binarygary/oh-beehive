# Phase 1: Parser Foundation - Research

**Researched:** 2026-04-04
**Domain:** AI Parsing Architecture, Laravel, Livewire, OpenAI Integration
**Confidence:** HIGH

## Summary

This phase focuses on stabilizing the AI parsing foundation by transitioning from an ambiguous, auto-debounced flow to an explicit, synchronous, and well-tested architecture. The current implementation relies on a debounce on the `rawNotes` textarea and an unused `ParseInspectionNotes` queue job. Phase 1 will remove this architectural ambiguity by introducing an explicit "Parse Notes" button, deleting the dead job, and wrapping the parser behind an interface to ensure tests never make real API calls.

**Primary recommendation:** Introduce `App\Contracts\InspectionParserInterface`, migrate `InspectionParserService` to implement it, and replace Livewire debounce logic with an explicit button trigger while stubbing the service in all feature tests.

<user_constraints>
## User Constraints (from 01-CONTEXT.md)

### Locked Decisions
- **D-01:** Replace `updatedRawNotes()` debounce with an explicit **Parse button** placed below the raw notes textarea. The `updatedRawNotes()` Livewire hook is removed entirely — parsing only fires on button click. [CITED: 01-CONTEXT.md]
- **D-02:** The existing "Analyzing…" spinner/loading indicator stays but is now triggered by the button click action, not by debounce. [CITED: 01-CONTEXT.md]
- **D-03:** Button position: directly below the textarea. Standard read-order — keeper types, then clicks. [CITED: 01-CONTEXT.md]
- **D-04:** A failed OpenAI call (any `\Throwable`) shows a **DaisyUI `alert-warning`** div below the textarea/button area. Non-blocking — structured fields remain visible and editable. [CITED: 01-CONTEXT.md]
- **D-05:** Error alert **auto-clears on the next successful parse**. No dismiss button. Keeper's natural retry action (clicking Parse again) clears it. [CITED: 01-CONTEXT.md]
- **D-06:** Alert text (approximate): "Could not parse notes. You can fill fields manually below." [CITED: 01-CONTEXT.md]
- **D-07:** Delete `app/Jobs/ParseInspectionNotes.php` entirely. No replacement — parsing is synchronous in the Livewire component. [CITED: 01-CONTEXT.md]
- **D-08:** Introduce `App\Contracts\InspectionParserInterface` with two methods: `parseRaw(string $rawNotes): array` and `parse(Inspection $inspection): void`. [CITED: 01-CONTEXT.md]
- **D-09:** `InspectionParserService` implements `InspectionParserInterface`. `AppServiceProvider` binds the interface to the concrete class. [CITED: 01-CONTEXT.md]
- **D-10:** A `FakeInspectionParserService` (or in-test anonymous class) implements the interface and returns canned field data. Tests bind it via `app()->instance(InspectionParserInterface::class, ...)` — no real OpenAI calls. [CITED: 01-CONTEXT.md]
- **D-11:** All callsites (`create.blade.php`, `edit.blade.php`) type-hint against `InspectionParserInterface`, not the concrete class. [CITED: 01-CONTEXT.md]
- **D-12:** Add **unit tests** for `InspectionParserService` covering happy path, null/missing fields, disease filtering, and short notes guard. [CITED: 01-CONTEXT.md]

### the agent's Discretion
- File location for the interface: `app/Contracts/InspectionParserInterface.php` (standard Laravel pattern). [CITED: 01-CONTEXT.md]
- Whether to use a standalone `FakeInspectionParserService.php` file or an anonymous class inline in tests. [CITED: 01-CONTEXT.md]
- Exact DaisyUI classes for the alert (`alert alert-warning`). [CITED: 01-CONTEXT.md]
- Whether the Parse button is `btn-primary` or `btn-secondary` (`btn-primary` recommended). [CITED: 01-CONTEXT.md]

### Deferred Ideas (OUT OF SCOPE)
- User self-registration — accounts created via artisan only. [CITED: PROJECT.md]
- Real-time live dictation — audio upload is sufficient. [CITED: PROJECT.md]
- Paid tiers / monetization. [CITED: PROJECT.md]
- Hive type selection — all Langstroth. [CITED: PROJECT.md]
- Treatment as structured field — free text is sufficient. [CITED: PROJECT.md]
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PARSE-01 | Remove dead `ParseInspectionNotes` queue job (dead code, architectural ambiguity risk). | Verified job existence and confirmed deletion plan. |
| PARSE-08 | OpenAI client is faked/stubbed in tests so parsing tests don't make real API calls. | Verified `openai-php/client` usage and established mocking/stubbing pattern. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| laravel/framework | ^13.0 | Backend Framework | Project base [VERIFIED: composer.json] |
| livewire/livewire | ^3.6.4 | Reactive UI Component | Project base [VERIFIED: composer.json] |
| livewire/volt | ^1.7.0 | Single-file Component | Component structure [VERIFIED: composer.json] |
| openai-php/client | ^0.19.1 | OpenAI API Communication | Official PHP client [VERIFIED: composer.json] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| pestphp/pest | ^4.4 | Testing Framework | Project test runner [VERIFIED: composer.json] |
| mockery/mockery | ^1.6 | Mocking Objects | Unit testing service dependencies [VERIFIED: composer.json] |
| daisyui | v5 | UI Components | Styling (alert, button, loading) [VERIFIED: app.css] |

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Contracts/
│   └── InspectionParserInterface.php   # NEW: Service interface
├── Services/
│   └── InspectionParserService.php     # MODIFIED: Implements interface
└── Jobs/                               # MODIFIED: ParseInspectionNotes.php deleted
resources/views/livewire/pages/inspections/
├── create.blade.php                    # MODIFIED: Synchronous parse button
└── edit.blade.php                      # MODIFIED: Synchronous parse button
tests/
├── Feature/
│   └── InspectionTest.php              # MODIFIED: Service faked/stubbed
└── Unit/
    └── Services/
        └── InspectionParserServiceTest.php # NEW: Unit tests for logic
```

### Pattern 1: Service Contract Pattern
**What:** Define an interface for the AI parser and bind it in the Service Container.
**When to use:** When a service has external side effects (like API calls) that need to be stubbed in tests.
**Example:**
```php
namespace App\Contracts;
use App\Models\Inspection;

interface InspectionParserInterface {
    public function parseRaw(string $rawNotes): array;
    public function parse(Inspection $inspection): void;
}
```

### Anti-Patterns to Avoid
- **Hard-coding concrete services in tests:** Makes tests slow and dependent on external APIs.
- **Debounced API calls for expensive resources:** `gpt-4o-mini` calls are cheap but adds UI flicker/delay. Explicit button click is more intentional for beekeepers.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| AI Parsing | Custom regex/NLP | OpenAI GPT-4o-mini | High reliability for unstructured text to JSON extraction. |
| JSON Mocking | String templates | `CreateResponse::from([...])` | Provided by `openai-php/client` to create real response objects. |

## Common Pitfalls

### Pitfall 1: Debounce Race Conditions
**What goes wrong:** User types quickly, debounce fires multiple times, older response overwrites newer response in UI.
**Why it happens:** Livewire debounce doesn't cancel previous requests unless explicitly handled.
**How to avoid:** Use an explicit Parse button with `wire:loading.attr="disabled"` to prevent multiple concurrent calls.

### Pitfall 2: Silent API Failures
**What goes wrong:** OpenAI call fails (quota, network), UI does nothing, user thinks it's still "analyzing" or just broken.
**Why it happens:** Exceptions swallowed in `try-catch` without UI feedback.
**How to avoid:** Implement a `$parseError` state and show a visible notice (`alert-warning`).

## Code Examples

### Mocking OpenAI Client (Unit Test)
```php
// Source: WebSearch verification
use OpenAI\Responses\Chat\CreateResponse;
use Mockery;

$mockResponse = CreateResponse::from([
    'choices' => [
        [
            'message' => [
                'role' => 'assistant',
                'content' => json_encode(['queen_seen' => true]),
            ],
        ],
    ],
]);

$clientMock = Mockery::mock(OpenAI\Client::class);
$clientMock->shouldReceive('chat->create')->andReturn($mockResponse);
```

### Swapping Service for Fake (Feature Test)
```php
// Source: 01-CONTEXT.md D-10
app()->instance(InspectionParserInterface::class, new class implements InspectionParserInterface {
    public function parseRaw(string $rawNotes): array {
        return ['queen_seen' => true];
    }
    public function parse(Inspection $inspection): void {}
});
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Queued Parsing | Synchronous Parsing | Phase 1 | Simpler flow, immediate feedback for user. |
| Debounce Trigger | Explicit Button Trigger | Phase 1 | Reduced API calls, intentional action. |

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `openai-php/client` is used directly in `AppServiceProvider`. | Standard Stack | Binding logic might differ. [VERIFIED: Codebase grep] |
| A2 | DaisyUI v5 is used for alerts. | Standard Stack | UI styling might be inconsistent. [VERIFIED: app.css] |

## Open Questions

1. **Short notes threshold:** D-12 mentions a "Short notes guard" for input < 15 chars. Is this 15 characters exactly, and should it be visually communicated?
   - *Recommendation:* Disable the Parse button if input length is less than 15.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | Core runtime | ✓ | 8.3 | — |
| composer | Package management | ✓ | 2.x | — |
| SQLite | Database | ✓ | 3.x | — |
| OpenAI API Key | Production parsing | ✗ | — | Stubbed in tests |

**Missing dependencies with no fallback:**
- None — the environment is fully capable of running tests and development with fakes.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 4 |
| Config file | `phpunit.xml` |
| Quick run command | `composer test` |
| Full suite command | `php vendor/bin/pest` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PARSE-01 | Deletion of Job | Unit (Arch) | `php vendor/bin/pest tests/Architecture/ArchTest.php` | ✅ |
| PARSE-08 | OpenAI Faking | Feature | `composer test tests/Feature/InspectionTest.php` | ✅ |
| SC-1 | UI Population | Feature | `composer test tests/Feature/InspectionTest.php` | ✅ |
| SC-4 | Error Alert | Feature | `composer test tests/Feature/InspectionTest.php` | ✅ |

### Wave 0 Gaps
- [ ] `tests/Unit/Services/InspectionParserServiceTest.php` — NEW: Unit tests for parser logic.
- [ ] `app/Contracts/InspectionParserInterface.php` — NEW: Interface for dependency injection.

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V5 Input Validation | yes | AI responses are type-coerced and validated before use. |
| V10 Malicious Code | no | Job deletion reduces architectural surface. |

### Known Threat Patterns for AI Parsing

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| AI Hallucination | Information Disclosure | Type-coerced field extraction and user review step. |
| Invalid JSON Response | Tampering | `json_decode` error handling + non-blocking UI alert. |

## Sources

### Primary (HIGH confidence)
- `app/Services/InspectionParserService.php` - Current logic verified.
- `composer.json` - Library versions verified.
- `01-CONTEXT.md` - Phase boundaries and decisions verified.
- `01-UI-SPEC.md` - UI design and copy verified.

### Secondary (MEDIUM confidence)
- `openai-php/client` docs - Mocking strategies for chat responses.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Verified via composer.json.
- Architecture: HIGH - Defined in 01-CONTEXT.md.
- Pitfalls: HIGH - Common Livewire/API patterns.

**Research date:** 2026-04-04
**Valid until:** 2026-05-04
