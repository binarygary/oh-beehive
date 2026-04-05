# Phase 1: Parser Foundation - Context

**Gathered:** 2026-04-04
**Status:** Ready for planning

<domain>
## Phase Boundary

Delete the dead `ParseInspectionNotes` queue job, replace the auto-debounce parse trigger with an explicit Parse button, surface parse errors as a non-blocking alert, and make all tests safe by introducing `InspectionParserInterface` with a fake implementation bound in tests. No new features — this is foundation cleanup before any new capability work.

Requirements in scope: PARSE-01, PARSE-08 (and SC-1, SC-4 from Phase 1 success criteria).

</domain>

<decisions>
## Implementation Decisions

### Parse Trigger

- **D-01:** Replace `updatedRawNotes()` debounce with an explicit **Parse button** placed below the raw notes textarea. The `updatedRawNotes()` Livewire hook is removed entirely — parsing only fires on button click.
- **D-02:** The existing "Analyzing…" spinner/loading indicator stays but is now triggered by the button click action, not by debounce.
- **D-03:** Button position: directly below the textarea. Standard read-order — keeper types, then clicks.

### Error Handling

- **D-04:** A failed OpenAI call (any `\Throwable`) shows a **DaisyUI `alert-warning`** div below the textarea/button area. Non-blocking — structured fields remain visible and editable.
- **D-05:** Error alert **auto-clears on the next successful parse**. No dismiss button. Keeper's natural retry action (clicking Parse again) clears it.
- **D-06:** Alert text (approximate): "Could not parse notes. You can fill fields manually below."

### Dead Code Removal

- **D-07:** Delete `app/Jobs/ParseInspectionNotes.php` entirely. No replacement — parsing is synchronous in the Livewire component.

### OpenAI Test Stub

- **D-08:** Introduce `App\Contracts\InspectionParserInterface` with two methods: `parseRaw(string $rawNotes): array` and `parse(Inspection $inspection): void`. This is a **minimal interface** — only the two public methods of the existing service.
- **D-09:** `InspectionParserService` implements `InspectionParserInterface`. `AppServiceProvider` binds the interface to the concrete class.
- **D-10:** A `FakeInspectionParserService` (or in-test anonymous class) implements the interface and returns canned field data. Tests bind it via `app()->instance(InspectionParserInterface::class, ...)` — no real OpenAI calls.
- **D-11:** All callsites (`create.blade.php`, `edit.blade.php`) type-hint against `InspectionParserInterface`, not the concrete class.

### Test Coverage

- **D-12:** Add **unit tests** for `InspectionParserService` covering:
  - Happy path: all field types (boolean, integer, string, enum, array) correctly extracted from a complete JSON response
  - Null/missing field handling: missing or null JSON fields are absent from the output array
  - Invalid disease filtering: `disease_observations` only contains allowlist values
  - Short notes guard: `parseRaw()` on input shorter than 15 chars returns early without calling OpenAI

### Claude's Discretion

- File location for the interface: `app/Contracts/InspectionParserInterface.php` (follows standard Laravel contracts pattern)
- Whether to use a standalone `FakeInspectionParserService.php` file or an anonymous class inline in tests — either is acceptable
- Exact DaisyUI classes for the alert (use `alert alert-warning` consistent with existing DaisyUI v5 usage)
- Whether the Parse button is `btn-primary` or `btn-secondary` — pick what fits the form's visual hierarchy

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements

- `.planning/REQUIREMENTS.md` — PARSE-01 and PARSE-08 are the requirements for this phase; PARSE-02, PARSE-03, PARSE-04, PARSE-06 are already validated and must not be regressed

### Existing Code (Must Read)

- `app/Services/InspectionParserService.php` — the service being wrapped behind an interface; extractFields() and parseRaw() are the core logic under test
- `app/Jobs/ParseInspectionNotes.php` — the dead job to delete
- `app/Providers/AppServiceProvider.php` — where OpenAI\Client singleton is registered; interface binding goes here
- `resources/views/livewire/pages/inspections/create.blade.php` — primary callsite for the parser; parse trigger and error notice go here
- `resources/views/livewire/pages/inspections/edit.blade.php` — secondary callsite; same changes apply

### Tests (Must Read)

- `tests/Feature/InspectionTest.php` — existing feature tests that must remain passing after stub is in place
- `tests/Architecture/ArchTest.php` — strict types and structural rules that new files must satisfy

### Conventions

- `.planning/codebase/CONVENTIONS.md` — strict types required, PHPDoc rules, method design
- `.planning/codebase/TESTING.md` — test patterns (Volt::test, factories, in-memory SQLite)

</canonical_refs>
