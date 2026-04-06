# Phase 02: Field Provenance - Research

**Researched:** 2026-04-06
**Domain:** Livewire/Volt form-state provenance UI for AI-populated inspection fields
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

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

### Deferred Ideas (OUT OF SCOPE)

None — analysis stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PARSE-05 | Fields filled by AI are visually distinguished from fields entered manually | Use a component-local `$aiFilledFields` map, label-level DaisyUI badge rendering, and Livewire update hooks plus selective `wire:model` modifiers so provenance clears on keeper edits without any schema change. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`, `https://livewire.laravel.com/docs/3.x/lifecycle-hooks`, `https://livewire.laravel.com/docs/3.x/wire-model`, `https://daisyui.com/components/badge/?lang=en`] |
</phase_requirements>

## Summary

This phase is a UI-only extension to the existing Volt inspection form components in [`resources/views/livewire/pages/inspections/create.blade.php`](/Users/garykovar/projects/codeable/oh-beehive/resources/views/livewire/pages/inspections/create.blade.php) and [`resources/views/livewire/pages/inspections/edit.blade.php`](/Users/garykovar/projects/codeable/oh-beehive/resources/views/livewire/pages/inspections/edit.blade.php). The parser already returns the relevant structured keys, and the current components already assign those keys into public Livewire properties, so provenance can be layered on top of the existing assignment flow without touching the database, model casts, or service contract. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `app/Services/InspectionParserService.php`, `app/Models/Inspection.php`]

The only planning-critical trap is Livewire’s update timing. In Livewire 3, plain `wire:model` synchronizes on the next action, not immediately when the user types or selects a value. That means an `updated()` hook alone will not clear an “AI” indicator immediately for text, number, select, and checkbox inputs that currently use plain `wire:model`. To meet the intended UX, the plan should include selective binding upgrades such as `wire:model.blur` for text/number/textarea inputs and `wire:model.change` for select/checkbox inputs, while keeping button-driven controls on their current action-based flow. [CITED: https://livewire.laravel.com/docs/3.x/wire-model] [VERIFIED: `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`]

**Primary recommendation:** Implement provenance as a component-local field map populated during `parse()`, cleared through Livewire update hooks, and rendered as a small DaisyUI badge beside every parser-populated field label; include `wire:model.blur` / `wire:model.change` changes where needed so manual overrides clear the badge at the moment the keeper edits the field. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `https://livewire.laravel.com/docs/3.x/lifecycle-hooks`, `https://livewire.laravel.com/docs/3.x/wire-model`, `https://daisyui.com/components/badge/?lang=en`]

## Project Constraints (from CLAUDE.md)

- Stay on `Laravel 13 + Livewire v3 + Volt`; no framework changes are allowed. [VERIFIED: `CLAUDE.md`, `composer.json`]
- Keep Tailwind configuration in `resources/css/app.css`; this project does not use `tailwind.config.js`. [VERIFIED: `CLAUDE.md`]
- Use DaisyUI v5 conventions for UI additions. [VERIFIED: `CLAUDE.md`, `package.json`, `npm view daisyui version time.modified`]
- Tests run with Pest and Volt testing helpers on in-memory SQLite. [VERIFIED: `CLAUDE.md`, `.planning/codebase/TESTING.md`, `composer show pestphp/pest --format=json`]
- Any new PHP files under `app/` would require `declare(strict_types=1);`, but this phase can likely stay inside existing Volt Blade files and test files. [VERIFIED: `CLAUDE.md`, `.planning/codebase/CONVENTIONS.md`]
- GrumPHP enforces Larastan, Pint, and the Pest suite on commit, so the phase plan should include lint and test verification before completion. [VERIFIED: `CLAUDE.md`, `composer.json`]
- Optimize for reviewability: foundation before behavior/UI, small independently reviewable PRs, and avoid mixing unrelated refactors into this phase. [VERIFIED: user-provided AGENTS instructions in prompt]

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | `13.3.0` installed, `13.3.0` latest, released `2026-04-01` | Host app, validation, auth, Blade rendering | This phase stays inside the existing Laravel form and auth model instead of introducing new infrastructure. [VERIFIED: `composer show laravel/framework --latest --format=json`] |
| Livewire | `3.7.13` installed, `4.2.4` latest major, released `2026-03-30` for installed version | Stateful server-driven form updates and lifecycle hooks | The repo is intentionally pinned to Livewire v3, and Livewire’s `updated()`/`updated{Property}` hooks are the standard server-side mechanism for clearing per-field provenance. [VERIFIED: `composer.json`, `composer show livewire/livewire --latest --format=json`] [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks] |
| Livewire Volt | `1.10.5` installed and latest, released `2026-03-18` | Single-file page components and `Volt::test()` | Both inspection forms are already Volt page components, so provenance should be added in-place rather than extracted to a new component style. [VERIFIED: `composer show livewire/volt --latest --format=json`, `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`] [CITED: https://livewire.laravel.com/docs/3.x/volt] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| DaisyUI | `5.5.19` installed and latest, npm modified `2026-02-20` | Badge styling for the AI provenance marker | Use for the label-level “AI” indicator because the project already uses DaisyUI badges and the docs explicitly expose `badge`, `badge-sm`, and `badge-primary`. [VERIFIED: `package.json`, `npm view daisyui version time.modified`, `resources/views/livewire/pages/inspections/index.blade.php`] [CITED: https://daisyui.com/components/badge/?lang=en] |
| Tailwind CSS | `4.2.2` installed and latest, npm modified `2026-04-03` | Conditional utility classes around labels/wrappers | Use through existing Blade `@class` patterns; no custom CSS system is needed for this phase. [VERIFIED: `package.json`, `npm view tailwindcss version time.modified`, `resources/views/livewire/pages/inspections/create.blade.php`] |
| Pest | `4.4.3` installed, `4.4.5` latest, released `2026-03-21` for installed version | Feature-level verification of component state transitions | Use for `Volt::test()` coverage that proves provenance population and clearing. [VERIFIED: `composer show pestphp/pest --latest --format=json`, `.planning/codebase/TESTING.md`] [CITED: https://livewire.laravel.com/docs/3.x/testing] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Component-local `$aiFilledFields` map | Database column or JSON provenance payload | Persisted provenance would violate the locked phase boundary and add schema churn for a purely presentational concern. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `app/Models/Inspection.php`] |
| Blade-rendered badge | Alpine/JS badge toggling | JavaScript is explicitly out of scope for this phase and unnecessary because Livewire hooks already provide the needed state transitions. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`] [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks] |
| Selective `wire:model.blur` / `.change` | Leaving all fields on plain `wire:model` | Plain `wire:model` delays updates until the next action, which would leave stale provenance badges visible after manual edits. [CITED: https://livewire.laravel.com/docs/3.x/wire-model] |

**Installation:**
```bash
# No new packages required for this phase.
```

**Version verification:** Versions above were verified from the installed dependency graph and current registries using `composer show ... --latest --format=json` and `npm view ... version time.modified`. [VERIFIED: local command output captured during research]

## Architecture Patterns

### Recommended Project Structure
```text
resources/views/livewire/pages/inspections/
├── create.blade.php   # Add aiFilledFields state, parse() population, update hooks, label badges
└── edit.blade.php     # Mirror the same provenance behavior

tests/Feature/
└── InspectionTest.php # Add provenance-focused Volt feature tests
```

### Pattern 1: Provenance Map Mirrors Parser Output
**What:** Add a public `array<string, bool>` map keyed by Livewire property name, and populate it only for fields actually present in the parser response. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `app/Services/InspectionParserService.php`]

**When to use:** Use on every successful `parse()` call in both inspection pages, immediately alongside the existing field-assignment block. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`]

**Example:**
```php
<?php
// Source: locked phase context + existing parse() callsites

public array $aiFilledFields = [];

public function parse(): void
{
    $this->aiFilledFields = [];

    // existing parser call...

    if (array_key_exists('queen_seen', $fields)) {
        $this->queenSeen = $nb($fields['queen_seen']);
        $this->aiFilledFields['queenSeen'] = true;
    }
}
```

### Pattern 2: Clear Provenance Through Livewire Update Hooks
**What:** Use Livewire update hooks to remove the single field key from `$aiFilledFields` when the keeper overrides a parser-populated value. [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks]

**When to use:** Use a generic `updated($property)` hook for scalar properties, and a dedicated `updatedDiseaseObservations($value, $key)` hook for the checkbox array field because Livewire documents array-specific hook arguments separately. [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks]

**Example:**
```php
<?php
// Source: Livewire 3 lifecycle hook docs

public function updated(string $property): void
{
    unset($this->aiFilledFields[$property]);
}

public function updatedDiseaseObservations($value, $key): void
{
    unset($this->aiFilledFields['diseaseObservations']);
}
```

### Pattern 3: Upgrade Only the Inputs That Need Immediate Sync
**What:** Keep button-driven controls as-is, but change text/number/textarea inputs to `wire:model.blur` and select/checkbox inputs to `wire:model.change` where immediate provenance clearing matters. [CITED: https://livewire.laravel.com/docs/3.x/wire-model]

**When to use:** Apply to parser-populated controls that currently use plain `wire:model`, including `weather`, `framesOfBrood`, `framesOfBees`, `framesOfHoney`, `varroaCount`, `feedingNotes`, `treatmentApplied`, `supersAdded`, `supersRemoved`, `queenStatus`, `varroaMethod`, and `diseaseObservations`. [VERIFIED: `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`]

### Anti-Patterns to Avoid
- **Persisting provenance to the database:** This phase is explicitly scoped as UI state only; persisting it creates unnecessary schema and migration work. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `app/Models/Inspection.php`]
- **Assuming `updated()` fires immediately with plain `wire:model`:** Livewire 3 explicitly documents action-based syncing for plain `wire:model`. [CITED: https://livewire.laravel.com/docs/3.x/wire-model]
- **Mixing parser refactors into this phase:** The parser service already exposes the necessary keys; changing parser behavior would violate the phase boundary and reduce reviewability. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `app/Services/InspectionParserService.php`, user-provided AGENTS instructions in prompt]

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Visual status chip | Custom CSS badge system | DaisyUI `badge badge-sm badge-primary` | The project already uses DaisyUI badges and the component library documents the exact classes needed. [VERIFIED: `resources/views/livewire/pages/inspections/index.blade.php`, `package.json`] [CITED: https://daisyui.com/components/badge/?lang=en] |
| Manual override detection | Client-side JS diffing between original and current form state | Livewire `updated()` / `updated{Property}` hooks | Livewire already provides the server-side lifecycle hooks for property updates, which matches the phase’s “no JS” constraint. [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks] |
| Parser-key normalization at render time | Repeated ad hoc `snake_case` to `camelCase` conversions in Blade | A single explicit assignment/provenance map inside `parse()` | The current code already performs explicit field assignments in `parse()`, so provenance should be set in the same place for clarity and reviewability. [VERIFIED: `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`] |

**Key insight:** The codebase already has the hard part: a canonical parser-response-to-property assignment list. Provenance should attach to that list, not infer changes later from database state or HTML state. [VERIFIED: `app/Services/InspectionParserService.php`, `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`]

## Common Pitfalls

### Pitfall 1: Stale AI Badge After Manual Edit
**What goes wrong:** The keeper edits a field, but the “AI” badge stays visible until Save or Parse is clicked. [CITED: https://livewire.laravel.com/docs/3.x/wire-model]
**Why it happens:** Most parser-populated inputs currently use plain `wire:model`, which does not send an update request until the next action. [CITED: https://livewire.laravel.com/docs/3.x/wire-model] [VERIFIED: `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`]
**How to avoid:** Plan binding changes for non-button inputs so the server receives the edit event when the keeper blurs or changes the control. [CITED: https://livewire.laravel.com/docs/3.x/wire-model]
**Warning signs:** Feature tests that only use `set()` will pass, but browser behavior will lag if markup keeps plain `wire:model`. [INFERENCE from cited Livewire docs and current templates]

### Pitfall 2: Stale Provenance Keys After Re-Parse
**What goes wrong:** Fields marked by a previous parse remain flagged even if the latest parser response omits them. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`]
**Why it happens:** A reused provenance array that is only appended to will accumulate obsolete keys. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`]
**How to avoid:** Reset `$aiFilledFields` at the top of each `parse()` run before repopulating it from the fresh parser output. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`]
**Warning signs:** A field keeps the badge even after a second parse no longer returns that field. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`]

### Pitfall 3: Array Field Provenance Not Clearing Cleanly
**What goes wrong:** `diseaseObservations` remains marked as AI-filled after a keeper checks or unchecks a disease checkbox. [VERIFIED: `app/Services/InspectionParserService.php`, `resources/views/livewire/pages/inspections/create.blade.php`]
**Why it happens:** Array properties need dedicated hook handling if you want predictable behavior for per-item updates. [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks]
**How to avoid:** Add an explicit `updatedDiseaseObservations($value, $key)` hook that clears the single top-level provenance flag for the whole array field. [CITED: https://livewire.laravel.com/docs/3.x/lifecycle-hooks]
**Warning signs:** Scalar fields clear correctly, but disease checkboxes do not. [INFERENCE from cited Livewire docs and current field structure]

## Code Examples

Verified patterns from official sources:

### Livewire Update Hook
```php
<?php
// Source: https://livewire.laravel.com/docs/3.x/lifecycle-hooks

public function updated($property)
{
    if ($property === 'username') {
        $this->username = strtolower($this->username);
    }
}
```

### Volt Feature Test Pattern
```php
<?php
// Source: https://livewire.laravel.com/docs/3.x/volt

use Livewire\Volt\Volt;

it('increments the counter', function () {
    Volt::test('counter')
        ->assertSee('0')
        ->call('increment')
        ->assertSee('1');
});
```

### Current Integration Point in This Repo
```php
<?php
// Source: existing inspection form parse() flow

if (array_key_exists('queen_seen', $fields)) {
    $this->queenSeen = $nb($fields['queen_seen']);
}

$fq = $fields['followup_questions'] ?? null;
$this->followupQuestions = is_array($fq) ? $fq : [];
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Plain `wire:model` everywhere | Use `wire:model.blur` / `.change` when server-side reactions must happen immediately | Livewire 3 docs describe action-based default syncing and modifier-based eager syncing | Provenance-clearing behavior must be planned at the template binding level, not only in PHP hooks. [CITED: https://livewire.laravel.com/docs/3.x/wire-model] |
| Persisting parser outputs only | Keep parser outputs editable and layer transient provenance state on top | Existing phase decisions for Phase 02 | The keeper can still overwrite AI values without any persistence-model changes. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `.planning/REQUIREMENTS.md`] |

**Deprecated/outdated:**
- Relying on the old assumption that `wire:model` updates server state on every keystroke is outdated for Livewire 3. [CITED: https://livewire.laravel.com/docs/3.x/wire-model]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | The planner should treat the “clear when manually edited” requirement as immediate visual feedback, not “clear on next action only.” [ASSUMED] | Summary, Architecture Patterns, Common Pitfalls | The implementation could technically satisfy tests while feeling wrong in the browser if the product owner only wanted eventual clearing. |

## Open Questions (RESOLVED)

1. **Should every parser-populated field get the AI badge, including free-text fields like `weather`, `feedingNotes`, and `treatmentApplied`?**
   Resolution: **Yes.** Phase 02 plans now treat every parser-populated structured field as provenance-aware so PARSE-05 stays consistent across booleans, selects, numeric inputs, text inputs, textareas, and the disease checkbox group. [VERIFIED: `.planning/phases/02-field-provenance/02-01-PLAN.md`, `.planning/phases/02-field-provenance/02-02-PLAN.md`, `.planning/phases/02-field-provenance/02-UI-SPEC.md`]
   Why: The requirement says AI-filled fields should be visually distinguished, and the approved UI-SPEC locks a label-level badge treatment that remains light enough to apply broadly without adding noisy input chrome. [VERIFIED: `.planning/REQUIREMENTS.md`, `.planning/phases/02-field-provenance/02-UI-SPEC.md`]

## Environment Availability

This phase has no external runtime dependency beyond the repo’s normal PHP/Composer/Node toolchain because it is a code-and-test-only Livewire/Blade change. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`, `composer.json`, `package.json`]

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | Laravel app and Pest tests | ✓ [VERIFIED: `php --version`] | `8.4.19` [VERIFIED: `php --version`] | — |
| Composer | Laravel dependencies and verification commands | ✓ [VERIFIED: `composer --version`] | `2.8.1` [VERIFIED: `composer --version`] | — |
| Node.js | Frontend asset toolchain if a build check is needed | ✓ [VERIFIED: `node --version`] | `22.12.0` [VERIFIED: `node --version`] | — |
| npm | Tailwind/DaisyUI dependency verification and frontend builds | ✓ [VERIFIED: `npm --version`] | `10.9.0` [VERIFIED: `npm --version`] | — |

**Missing dependencies with no fallback:**
- None. [VERIFIED: local environment audit during research]

**Missing dependencies with fallback:**
- None. [VERIFIED: local environment audit during research]

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest `4.4.3` + Volt testing helpers [VERIFIED: `composer show pestphp/pest --format=json`, `.planning/codebase/TESTING.md`] |
| Config file | `phpunit.xml` [VERIFIED: `.planning/codebase/TESTING.md`] |
| Quick run command | `./vendor/bin/pest tests/Feature/InspectionTest.php --filter provenance` [RECOMMENDED based on existing Pest setup] |
| Full suite command | `composer test` [VERIFIED: `composer.json`, `.planning/codebase/TESTING.md`] |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PARSE-05 | `parse()` marks parser-returned fields as AI-filled in create form | feature / Volt component | `./vendor/bin/pest tests/Feature/InspectionTest.php --filter "create.*ai"` | `tests/Feature/InspectionTest.php` exists; provenance test missing [VERIFIED: `tests/Feature/InspectionTest.php`] |
| PARSE-05 | Manual edit clears one field’s provenance in create form | feature / Volt component | `./vendor/bin/pest tests/Feature/InspectionTest.php --filter "create.*clears"` | `tests/Feature/InspectionTest.php` exists; provenance test missing [VERIFIED: `tests/Feature/InspectionTest.php`] |
| PARSE-05 | `parse()` marks parser-returned fields as AI-filled in edit form | feature / Volt component | `./vendor/bin/pest tests/Feature/InspectionTest.php --filter "edit.*ai"` | `tests/Feature/InspectionTest.php` exists; provenance test missing [VERIFIED: `tests/Feature/InspectionTest.php`] |
| PARSE-05 | Manual edit clears one field’s provenance in edit form | feature / Volt component | `./vendor/bin/pest tests/Feature/InspectionTest.php --filter "edit.*clears"` | `tests/Feature/InspectionTest.php` exists; provenance test missing [VERIFIED: `tests/Feature/InspectionTest.php`] |

### Sampling Rate
- **Per task commit:** `./vendor/bin/pest tests/Feature/InspectionTest.php --filter provenance` [RECOMMENDED]
- **Per wave merge:** `composer test` [VERIFIED: `composer.json`]
- **Phase gate:** `composer test` plus a targeted browser/manual check that badge clearing occurs on blur/change, not only after Save. [INFERENCE from Livewire sync semantics]

### Wave 0 Gaps
- [ ] Add create-form provenance tests to `tests/Feature/InspectionCreateTest.php` for parse population, single-field clearing, and re-parse refresh. [VERIFIED: `.planning/phases/02-field-provenance/02-01-PLAN.md`]
- [ ] Add edit-form provenance tests to `tests/Feature/InspectionEditTest.php` for parse population, single-field clearing, and re-parse refresh. [VERIFIED: `.planning/phases/02-field-provenance/02-02-PLAN.md`]
- [ ] Bind a fake parser in those tests so `call('parse')` returns deterministic keys without external API access. [VERIFIED: `tests/Unit/FakeInspectionParserService.php`, `tests/Feature/InspectionParserTest.php`]

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes [VERIFIED: existing inspection pages require auth in tests] | Keep existing authenticated route/component access patterns unchanged. [VERIFIED: `tests/Feature/InspectionTest.php`] |
| V3 Session Management | no material change in this phase [VERIFIED: phase scope is UI state only] | Existing Laravel session handling remains sufficient. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`] |
| V4 Access Control | yes [VERIFIED: edit/create components enforce user-scoped access and hive validation] | Preserve current `auth()` scoping and authorization behavior while adding provenance state. [VERIFIED: `resources/views/livewire/pages/inspections/edit.blade.php`, `resources/views/livewire/pages/inspections/create.blade.php`, `tests/Feature/InspectionTest.php`] |
| V5 Input Validation | yes [VERIFIED: existing save() validation remains in scope] | Reuse existing Laravel validation rules; provenance state must not bypass or replace them. [VERIFIED: `resources/views/livewire/pages/inspections/create.blade.php`, `resources/views/livewire/pages/inspections/edit.blade.php`] |
| V6 Cryptography | no [VERIFIED: no secrets or crypto behavior are added in this phase] | None needed. [VERIFIED: phase scope and current codepaths] |

### Known Threat Patterns for Laravel + Livewire form state

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Unauthorized inspection editing | Elevation of Privilege | Keep existing `abort_if($inspection->user_id !== auth()->id(), 403)` and user-scoped hive validation untouched. [VERIFIED: `resources/views/livewire/pages/inspections/edit.blade.php`, `resources/views/livewire/pages/inspections/create.blade.php`] |
| Trusting client-visible provenance for persistence decisions | Tampering | Treat `$aiFilledFields` as ephemeral presentation state only; never persist it or use it to authorize writes. [VERIFIED: `.planning/phases/02-field-provenance/02-CONTEXT.md`] |
| Rendering badge text unsafely | XSS | Keep badge content static (`AI`) in Blade instead of echoing user-controlled text. [INFERENCE from Blade escaping norms and locked badge decision] |

## Sources

### Primary (HIGH confidence)
- `https://livewire.laravel.com/docs/3.x/lifecycle-hooks` - verified `updated()` / `updated{Property}` behavior and array hook guidance.
- `https://livewire.laravel.com/docs/3.x/wire-model` - verified default sync timing plus `.blur` and `.change` modifiers.
- `https://livewire.laravel.com/docs/3.x/volt` - verified `Volt::test()` pattern.
- `https://livewire.laravel.com/docs/3.x/testing` - verified Livewire test API usage.
- `https://daisyui.com/components/badge/?lang=en` - verified badge component classes and size/color modifiers.
- `composer show laravel/framework --latest --format=json` - verified Laravel version and release date.
- `composer show livewire/livewire --latest --format=json` - verified installed Livewire v3 and current latest major.
- `composer show livewire/volt --latest --format=json` - verified Volt version and release date.
- `composer show pestphp/pest --latest --format=json` - verified Pest version and latest available patch.
- `npm view daisyui version time.modified` - verified DaisyUI version and currency.
- `npm view tailwindcss version time.modified` - verified Tailwind version and currency.
- `npm view @tailwindcss/forms version time.modified` - verified forms plugin version and currency.
- `resources/views/livewire/pages/inspections/create.blade.php` - verified current parse flow and field bindings.
- `resources/views/livewire/pages/inspections/edit.blade.php` - verified mirrored edit flow and field bindings.
- `app/Services/InspectionParserService.php` - verified parser output keys.
- `app/Models/Inspection.php` - verified no provenance persistence exists or is needed.
- `tests/Feature/InspectionTest.php` - verified current inspection coverage gaps.
- `tests/Unit/FakeInspectionParserService.php` - verified available fake parser pattern.
- `CLAUDE.md` - verified project stack and workflow constraints.

### Secondary (MEDIUM confidence)
- None.

### Tertiary (LOW confidence)
- None.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - versions were verified against installed dependencies and current registries, and the repo’s framework choices are locked. [VERIFIED: composer/npm version queries, `CLAUDE.md`, `composer.json`, `package.json`]
- Architecture: HIGH - the exact integration points, phase constraints, and Livewire lifecycle semantics were all verified directly in code and official docs. [VERIFIED: codebase files and Livewire docs]
- Pitfalls: HIGH - the main failure mode is directly documented by Livewire 3’s `wire:model` semantics and confirmed against current template bindings. [CITED: https://livewire.laravel.com/docs/3.x/wire-model] [VERIFIED: inspection form templates]

**Research date:** 2026-04-06
**Valid until:** 2026-05-06
