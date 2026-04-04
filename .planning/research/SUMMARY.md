# Project Research Summary

**Project:** Oh Beehive
**Domain:** Personal beekeeping inspection app — AI-assisted data capture, audio transcription, trend visualization
**Researched:** 2026-04-04
**Confidence:** HIGH

## Executive Summary

Oh Beehive is a single-user personal tool built on a locked-in stack (Laravel 13, Livewire v3 + Volt, Tailwind v4, DaisyUI v5, SQLite). The app has a complete data model and working CRUD, but the core value proposition — AI parsing inspection notes into structured fields — is wired only halfway: the service exists, the queue job is dead code, and the live-parse hook fires too aggressively. This milestone adds three capabilities on top of that working foundation: AI parsing wired end-to-end, audio upload via Whisper transcription, and per-hive inspection trend charts. No new Composer packages are required; the only new npm dependency is Chart.js.

The recommended approach is deliberately conservative: synchronous AI calls (not queued), ephemeral audio handling (transcribe and discard), and Chart.js injected via Alpine.js `x-init` (not a charting framework). Every design decision favors minimizing infrastructure complexity for a personal-scale tool. The review-before-save flow — keeper sees AI-filled fields before committing — is the architectural spine everything else must protect. Any pattern that breaks this review step (queued parse-on-save, auto-parsing raw Whisper output without display) should be treated as a blocker, not a trade-off.

The critical risks are all in Phase 1 (AI wiring): the live-parse debounce will silently overwrite manual edits without provenance tracking; the existing queue job conflicts with the synchronous design and must be deleted; and the test suite will make real OpenAI API calls the moment parsing is wired unless a fake client is registered. These must be addressed in sequence before building audio or charts. Pitfalls in later phases (PHP upload limits for Whisper, null-as-zero in charts) are real but well-understood and straightforward to mitigate.

---

## Key Findings

### Recommended Stack

The existing stack is locked in and requires no framework changes. The only addition is `chart.js ^4.4.0` via npm for trend visualization. All OpenAI functionality (chat completions via `gpt-4o-mini`, audio transcription via `whisper-1`) is already available through the installed `openai-php/client ^0.19.1`. Livewire v3's built-in `WithFileUploads` trait handles audio file upload without any additional packages.

**Core technologies:**
- `openai-php/client ^0.19.1`: AI parsing + Whisper transcription — already installed, client registered as singleton; no facade needed
- `Livewire\WithFileUploads`: audio file upload — built into Livewire v3, handles chunked XHR upload, temp storage, cleanup
- `chart.js ^4.4.0`: trend charts — only new dependency; lighter than ApexCharts (200KB vs 500KB+), no CSS coupling, no server-side dependencies
- All Laravel-specific charting packages: explicitly avoid — all are unmaintained or fight Livewire's reactive model

**PHP environment settings (not packages, but blockers):**
- `upload_max_filesize = 25M`, `post_max_size = 26M`, `max_execution_time = 120` required for audio upload path

### Expected Features

**Must have (table stakes):**
- AI parsing guaranteed on every save — parser fires but save does not guarantee it ran; must be closed
- Parsed fields visible before save — already partially working; gap is the explicit review state
- AI field provenance tracking (`ai_filled_fields` JSON column) — required for safe re-parse and UI indicators
- Graceful AI failure — current catch-all swallows errors silently; must show a non-blocking notice
- Inspection list per hive — scoped browsing; the global list is insufficient

**Should have (differentiators):**
- Audio upload → Whisper → parse — hands-free recording; complete pipeline with one new service
- Inline follow-up question answers — editable inputs that append to `raw_notes` and re-parse
- Per-hive trend charts — line charts for health score, varroa count, brood pattern, frame counts over time
- Hive summary card showing last inspection data — quick health status without opening an inspection

**Defer (out of this milestone):**
- Re-parse on edit (needs provenance feature first to avoid clobbering manual edits)
- Seasonal reminders (low complexity but not in scope)
- Real-time live dictation (WebSockets infrastructure not justified for personal tool)
- Photo attachments, sharing, push notifications, offline/PWA

### Architecture Approach

The app is a three-layer monolith: Livewire components (Volt) orchestrate user interaction, a thin service layer wraps OpenAI calls, and Eloquent models own persistence. This structure should be preserved exactly. Two new components are required: `TranscriptionService` (wraps Whisper, returns a string, knows nothing about the parser) and a `hives/show.blade.php` Volt component (queries inspection history, injects chart data via `@js()`, uses Alpine `x-init` for Chart.js). The existing `InspectionParserService` is unchanged. The `ParseInspectionNotes` queue job stays dormant — do not activate it.

**Major components:**
1. `inspections/create.blade.php` (extended) — adds `WithFileUploads`, `parseAudio()` action, explicit parse trigger, field provenance tracking
2. `TranscriptionService` (new) — accepts file path, calls `audio()->transcribe()`, returns transcript string; single responsibility, independently testable
3. `hives/show.blade.php` (new) — loads inspection history, builds chart data arrays in `with()`, renders Canvas; Chart.js owns the canvas after init
4. `InspectionParserService` (existing, unchanged) — `parseRaw(string): array` remains the parse interface for both typed and transcribed notes

### Critical Pitfalls

1. **Live-parse debounce overwrites manual edits** — The current `updatedRawNotes()` hook fires on every 5-second pause and overwrites all fields unconditionally. Add a `$manuallyEdited` tracking array; skip AI values for fields the keeper has touched. Without this, keepers lose corrections silently.

2. **No fake OpenAI client in tests** — The moment parsing is wired, `composer test` hits the live API or swallows errors via the catch-all. Register `OpenAI::fake()` in `TestCase::setUp()` before writing any new parse code. The current test suite passes vacuously because errors are silently caught.

3. **Dead queue job creates a future conflict** — `ParseInspectionNotes` implements async parse-after-save, which directly conflicts with the review-before-save synchronous design. Delete the job. If left in place, a future developer will wire it and introduce a silent data-overwrite race condition.

4. **PHP/Nginx upload limits hit before OpenAI limits** — Default `upload_max_filesize = 2M` and Nginx `client_max_body_size = 1M` will reject typical inspection audio files (1–8 MB) with a silent 413 before the request reaches Laravel. Set limits before building the upload UI.

5. **Raw Whisper transcript fed directly to parser** — Spoken audio contains hesitations, self-corrections, and incomplete sentences. Piping raw transcript to `parseRaw()` produces worse field extraction than typed notes. Show the transcript to the keeper for review/editing before parsing.

6. **Chart nulls rendered as zero** — Nullable inspection fields (varroa_count, scores) will appear as data drops to zero rather than gaps. Use `->whereNotNull()` on chart queries and configure Chart.js null handling to break the line, not interpolate.

---

## Implications for Roadmap

Based on combined research, three phases in strict dependency order:

### Phase 1: AI Parsing End-to-End

**Rationale:** The AI parsing pipeline is the foundation for all other features. Audio transcription feeds into the same parse flow. Charts visualize data that only exists once parsing works reliably. Nothing else can be built on a broken or half-wired parser. All critical pitfalls (1, 2, 3) live here and must be resolved before any other work.

**Delivers:** A complete, trustworthy AI parse flow. Keeper types notes, clicks parse, sees AI-filled fields with provenance indicators, corrects as needed, saves. Graceful degradation when OpenAI is unavailable.

**Addresses:**
- AI parsing guaranteed on save
- AI field provenance (`ai_filled_fields` column + migration)
- Graceful AI failure notice
- Inline follow-up question answers (UI upgrade from read-only bullets)
- Rate limiting on parse action

**Must do first:**
- Register `OpenAI::fake()` in tests
- Delete `ParseInspectionNotes` queue job
- Replace unconditional field-set with provenance-aware merge
- Replace live debounce trigger with explicit "Parse Notes" button (or blur-triggered)
- Move hardcoded model string to `config('services.openai.model')`
- Add `safeInt()` / `safeBool()` helpers for fragile casts in `extractFields()`
- Consolidate disease list to single source of truth

**Avoids pitfalls:** 1 (overwrite on debounce), 2 (fake client), 3 (dead job conflict), 7 (hard-coded model), 8 (fragile casts), 9 (disease list drift), 11 (rate limiting), 12 (null vs [] convention)

---

### Phase 2: Audio Upload and Transcription

**Rationale:** Audio transcription is a pre-step that populates `raw_notes` — it terminates in the exact same parse flow established in Phase 1. If Phase 1 is solid, Phase 2 is a contained addition: one new service, one new form section, PHP config change.

**Delivers:** Keeper can record inspection observations on mobile, upload the audio file, review the transcript, and trigger the existing parse flow. No permanent audio storage.

**Uses:** `Livewire\WithFileUploads` (built-in), `openai-php/client audio()->transcribe()` (already installed), `whisper-1` model

**Implements:** `TranscriptionService` (new); extended `create.blade.php` with file input, `parseAudio()` action, loading state via `wire:loading`

**Must do first:**
- Set `upload_max_filesize = 25M`, `post_max_size = 26M`, `max_execution_time = 120` in dev environment and document for deployment
- Design transcript display step before auto-parsing (keeper reviews transcript, can edit, then clicks parse)
- Validate `mimes:mp3,mp4,m4a,wav,webm` and `max:25600` in the component before upload attempt

**Avoids pitfalls:** 4 (upload limits), 5 (raw transcript to parser without review)

---

### Phase 3: Inspection History Trends

**Rationale:** Charts are read-only views over saved inspection data. They cannot be built until the parse-and-save flow (Phase 1) works reliably enough to produce meaningful records. Building charts last also means the chart data will reflect real keeper behavior, not test fixtures.

**Delivers:** Per-hive history page with line charts for overall health score, varroa count, brood pattern score, and frames of bees over time. Hive summary card with last inspection data added to hive index.

**Uses:** `chart.js ^4.4.0` (only new npm dependency), Alpine.js `x-init`, `@js()` Blade helper

**Implements:** `hives/show.blade.php` Volt component; `with()` method builds chart arrays; `x-ignore` on chart container prevents Livewire DOM morphing from destroying Chart.js canvas

**Must do first:**
- Design null handling per metric series (`->whereNotNull('varroa_count')` for varroa, gap configuration in Chart.js)
- Use `x-ignore` directive on chart container to prevent Livewire re-render conflicts

**Avoids pitfalls:** 10 (nulls as zeros), 13 (authorization scope on any new routes)

---

### Phase Ordering Rationale

- **Phase 1 before Phase 2:** Audio transcription produces `raw_notes` text that feeds the parse flow. A broken or overwrite-prone parse flow in Phase 1 means Phase 2's output is untestable.
- **Phase 1 before Phase 3:** Charts visualize parsed field values (scores, varroa counts). Without reliable parsing, chart data is sparse or meaningless.
- **Phase 2 before Phase 3 is optional** — these two phases are independent of each other. If chart visualization is higher priority, Phase 3 can come before Phase 2. The dependency is both on Phase 1.
- **Delete the queue job at the start of Phase 1** — this is a precondition, not a task within Phase 1.

### Research Flags

Phases that need no additional research — patterns are fully verified:
- **Phase 1 (AI Wiring):** `InspectionParserService` and the Livewire component are both readable in the codebase. The fixes are mechanical. No unknown APIs.
- **Phase 2 (Audio):** `WithFileUploads` and `openai-php/client audio()->transcribe()` are both verified against installed vendor source. The only unknown is the deployment environment's PHP ini configuration.
- **Phase 3 (Charts):** Chart.js + Alpine.js `x-init` is a canonical pattern. `@js()` is a stable Laravel built-in. No research needed.

No phases require `/gsd:research-phase` during planning. All technology choices are verified against installed packages or well-established patterns.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified against `composer.json`, `package.json`, and installed vendor source files |
| Features | HIGH | Grounded in existing code inspection; feature boundaries are clear |
| Architecture | HIGH | Component boundaries verified against actual service and component code |
| Pitfalls | HIGH (critical) / MEDIUM (some) | Critical pitfalls verified against codebase; Whisper quality pitfall and chart null handling from training knowledge |

**Overall confidence:** HIGH

### Gaps to Address

- **Chart.js v4.4.x exact null-gap behavior:** The "gap mode" configuration was not verified against the specific Chart.js version being installed. Validate `spanGaps: false` behavior (the default) produces visual line breaks rather than interpolation when data points are null. Low risk — this is Chart.js default behavior and is well-documented.
- **Whisper transcript quality on beekeeping audio:** How well Whisper handles beekeeper speech patterns (frame counts, disease names, score language) is not verifiable without real recordings. Mitigation is already designed: show the transcript to the keeper before parsing, so they can correct it. No research needed; the mitigation is structural.
- **PHP ini values in the deployment environment:** `upload_max_filesize` and `post_max_size` must be set before audio upload works. The exact deployment target is not specified. Document the requirements; verify at deploy time.

---

## Sources

### Primary (HIGH confidence — verified against installed source)
- `vendor/livewire/livewire/src/Features/SupportFileUploads/WithFileUploads.php` — file upload trait API
- `vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php` — default upload rules
- `vendor/openai-php/client/src/Resources/Audio.php` — `transcribe()` method signature
- `vendor/openai-php/client/src/Responses/Audio/TranscriptionResponse.php` — `.text` response property
- `app/Services/InspectionParserService.php` — existing parse flow, system prompt, field extraction
- `app/Jobs/ParseInspectionNotes.php` — dead queue job pattern
- `resources/views/livewire/pages/inspections/create.blade.php` — current form, debounce hook, error handling
- `database/migrations/2026_04_02_102712_create_inspections_table.php` — full schema
- `app/Models/Inspection.php` — fillable fields, casts
- `tests/Feature/InspectionTest.php` — test coverage gaps
- `composer.json` / `package.json` — installed versions

### Secondary (MEDIUM confidence — training knowledge, multiple sources agree)
- OpenAI Whisper API: 25 MB file limit, supported formats (mp3, mp4, mpeg, mpga, m4a, ogg, wav, webm), `whisper-1` is the only production API model
- Chart.js 4.x: current stable major, Chart.js 3.x EOL, MIT license
- Laravel `RateLimiter::attempt()` API: stable across Laravel 10–13
- `OpenAI::fake()` test helper: available via `openai-php/laravel` (note: project uses `openai-php/client` directly; `Http::fake()` is the alternative)

---
*Research completed: 2026-04-04*
*Ready for roadmap: yes*
