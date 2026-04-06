# Phase 2: Field Provenance - Context

**Gathered:** 2026-04-06 (assumptions mode)
**Status:** Ready for planning

<domain>
## Phase Boundary

Visually distinguish AI-filled fields from manually entered fields on the inspection form. When the keeper clicks Parse and the AI populates structured fields, those fields receive a visual indicator. When the keeper manually edits a field, the indicator clears for that field. No new parsing logic, no database schema changes — this is purely a UI state and rendering concern.

Requirements in scope: PARSE-05

</domain>

<decisions>
## Implementation Decisions

### State Tracking

- **D-01:** Provenance is tracked as a Livewire component-level `array<string, bool>` property named `$aiFilledFields`, keyed by field name (e.g., `['queenSeen' => true, 'eggsPresent' => true]`). No database column or model change.
- **D-02:** `$aiFilledFields` is populated inside the `parse()` action, immediately after assigning each field from the parser's return array. Every field present in the parser's output gets a `true` entry.
- **D-03:** This property lives in both `create.blade.php` and `edit.blade.php` — both components need the same treatment since both have the Parse button (added in Phase 1).

### Visual Indicator Mechanism

- **D-04:** The visual distinction uses DaisyUI/Tailwind conditional classes applied at the **field wrapper or label level** via Blade's `@class` directive — consistent with the existing `@class` pattern used for score buttons.
- **D-05:** No JavaScript or Alpine.js is introduced. This is pure server-side state rendered via Blade.
- **D-06:** Visual style: a small `badge badge-sm badge-primary` label (e.g., "AI") appended next to the field label using `@if(in_array('fieldName', array_keys($aiFilledFields)))`. Minimal DOM change, readable at a glance.

### Provenance Clearing (Manual Override Detection)

- **D-07:** When the keeper manually changes a field, provenance for **that specific field** is cleared. Uses Livewire `updated{PropertyName}()` lifecycle hooks — the same pattern as `updatedRawNotes()` from Phase 1.
- **D-08:** Provenance is also wiped entirely when the keeper clicks Parse again — the `$aiFilledFields` array is reset before each parse run, then repopulated with the fresh result.
- **D-09:** Only fields that are actually present in the parser's output array get provenance. Fields the AI left unresolved (not in the return array) are not marked as AI-filled.

### Test Coverage

- **D-10:** Feature tests using `Volt::test()` asserting component state: `$aiFilledFields` is populated after `call('parse')` and cleared for a specific field after `set('fieldName', newValue)`.
- **D-11:** No new parser service unit tests — Phase 1 already covers that. Tests focus on the component behavior only.
- **D-12:** At minimum test: create component, set raw notes, call parse, assert `aiFilledFields` contains expected keys; then set one field manually, assert that key is removed from `aiFilledFields`.

### Claude's Discretion

- Exact badge text ("AI", "auto", or an icon) — pick what looks clean in DaisyUI v5
- Whether to also add a subtle `ring` or `border` on the input/select itself in addition to the label badge
- Which fields show the indicator: all parsed fields vs. only boolean/enum/numeric ones (text fields that auto-populate may feel different)
- Exact `updated{PropertyName}()` method strategy — can use a catch-all `updated(string $name)` hook if Livewire v3 supports it, to avoid 20 individual hook methods

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` §AI Parsing — PARSE-05 definition (fields filled by AI visually distinguished)

### Prior Phase Context
- `.planning/phases/01-parser-foundation/01-CONTEXT.md` — Phase 1 decisions; D-01 through D-12 are locked (interface, fake, parse button, error alert)

### Primary Callsites (Must Read)
- `resources/views/livewire/pages/inspections/create.blade.php` — primary inspection form; `parse()` action and field assignments are here
- `resources/views/livewire/pages/inspections/edit.blade.php` — secondary callsite; identical changes needed

### Model & Service
- `app/Models/Inspection.php` — Inspection model; no schema change needed, but read to understand field names
- `app/Services/InspectionParserService.php` — understand what keys the parser returns in its array

### Conventions
- `.planning/codebase/CONVENTIONS.md` — strict types, PHPDoc rules, `updated*` hook naming
- `.planning/codebase/TESTING.md` — `Volt::test()` patterns with `->set()`, `->call()`, `->assertSet()`

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `@class` directive in score buttons (`create.blade.php:279-283`) — exact pattern to use for conditional class toggling
- `badge badge-sm badge-success/warning/error` in `index.blade.php:90` — established badge pattern; use `badge-primary` for AI indicator
- `FakeInspectionParserService` from Phase 1 — use this in tests so `call('parse')` returns canned data without real API calls

### Established Patterns
- Livewire `updated{PropertyName}()` hooks — used for `updatedRawNotes()` already; add hooks for each inspection field or use `updated(string $name)` catch-all
- `parse()` Livewire action in both create/edit components — provenance population goes here, after field assignment loop
- All field names in the component are public properties (camelCase: `$queenSeen`, `$eggsPresent`, etc.) — `$aiFilledFields` keys must match these names

### Integration Points
- Both `create.blade.php` and `edit.blade.php` need the same `$aiFilledFields` property and `updated*` hooks
- The field assignment block in `parse()` (lines ~92–118 in create) is where provenance gets set alongside field values
- Template field wrappers already exist — add the badge conditional inside each label

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches for the visual indicator style, as long as it's clearly visible but not distracting. DaisyUI conventions apply.

</specifics>

<deferred>
## Deferred Ideas

None — analysis stayed within phase scope.

</deferred>

---

*Phase: 02-field-provenance*
*Context gathered: 2026-04-06*
