# Domain Pitfalls

**Domain:** Laravel + Livewire app with AI-assisted form filling, audio transcription, and data visualization
**Project:** Oh Beehive
**Researched:** 2026-04-04
**Confidence:** HIGH (based on direct codebase analysis) / MEDIUM (Whisper + charting patterns from training knowledge, flagged where applicable)

---

## Critical Pitfalls

These mistakes cause data loss, broken UX, or rework of already-shipped features.

---

### Pitfall 1: AI Parse Fires on Every Keystroke via `wire:model.live`

**What goes wrong:** The current `updatedRawNotes()` hook fires on every Livewire model update to `rawNotes`. The view uses `wire:model.live.debounce.5000ms`, so a parse fires after every 5-second pause while typing. A user who types slowly or pauses mid-sentence will trigger 3–5 API calls per inspection — including calls on half-finished sentences that produce garbage field values that overwrite any manual edits the keeper already made.

**Why it happens:** `wire:model.live` pushes to the server on every change event (debounced here). The handler has no guard beyond a 15-character minimum. There is no concept of "user has finished composing notes" — only "user stopped typing for 5 seconds."

**Consequences:**
- OpenAI costs multiply unexpectedly — easily 10x the naive estimate for an active user.
- Fields the keeper manually set (e.g., they set `broodPatternScore = 4` before finishing their notes) get overwritten by a mid-sentence parse that returns null, resetting the field to blank.
- A "Analyzing…" spinner appears repeatedly as the user types, which feels broken.

**Prevention:** Trigger parsing explicitly, not reactively. Add a "Parse Notes" button the keeper clicks after finishing their observations, or trigger only on an explicit blur event of the textarea with a minimum character gate. Never auto-overwrite a field the user has already manually set — implement a `$userEdited` tracking array and skip AI-set values for those fields.

**Warning signs:**
- More OpenAI API calls than expected when reviewing usage dashboard.
- User reports "my scores keep resetting."
- Feature tests pass but real usage feels jumpy.

**Phase:** AI Wiring (Phase 1 of this milestone). Address before wiring the parser end-to-end.

---

### Pitfall 2: `ParseInspectionNotes` Job Exists but Conflicts with Synchronous Design

**What goes wrong:** `app/Jobs/ParseInspectionNotes.php` is dead code. The create form already calls `parseRaw()` synchronously in `updatedRawNotes()`. If a future developer discovers the job and wires it up on `save()` without removing the live hook, parsing happens twice: once during typing (live) and once asynchronously after save. The second parse may overwrite user edits made after the AI pre-filled fields.

**Why it happens:** The job was scaffolded for a queue-based approach, then the implementation moved to a synchronous live-parse approach, but the job was never deleted. Two competing patterns exist in the codebase simultaneously.

**Consequences:**
- Double API cost on every save if both paths are active.
- Race condition: the queued job runs after the user has edited AI-filled fields, silently overwriting the keeper's corrections.
- Larastan will continue to accept the unused job class, so there is no compiler-level warning.

**Prevention:** Make an explicit architectural decision: synchronous live-parse (current design) or queue-based parse-on-save. Delete the job if choosing synchronous. Delete `updatedRawNotes()` if choosing queue-based. Document the decision in `PROJECT.md`. The current synchronous design is the right choice for a single-user personal tool — delete the job.

**Warning signs:**
- Both `ParseInspectionNotes` and `updatedRawNotes` are wired in at the same time.
- Feature tests for save do not mock OpenAI, meaning they will fail or make real API calls the moment parsing is wired to save.

**Phase:** AI Wiring (Phase 1). Resolve as the first action — delete the job or the hook before adding any new wiring.

---

### Pitfall 3: Feature Tests Will Make Real OpenAI API Calls When Parsing Is Wired

**What goes wrong:** The current test `can create an inspection with only required fields` calls `save()` directly. Once `updatedRawNotes()` (or any wired parse path) makes a real HTTP call to OpenAI, tests either hit the live API (slow, costs money, fails in CI without credentials) or throw an uncaught exception and pass vacuously because the catch block in `updatedRawNotes()` swallows all `Throwable` errors silently.

**Why it happens:** `InspectionParserService` has zero test coverage. No fake or mock is registered in the test environment. The `openai-php/laravel` package supports a fake client but it is not configured anywhere.

**Consequences:**
- Tests are meaningless: the catch-all `catch (\Throwable)` means a broken parser silently does nothing, and the test passes regardless.
- CI breaks the first time someone runs it without `OPENAI_API_KEY` set.
- If the API key IS set in CI, tests cost real money and are slow.

**Prevention:** Register `OpenAI::fake()` in `TestCase::setUp()` or use `Http::fake()` at the test level. Add a dedicated unit test suite for `InspectionParserService` using a fixture JSON response — this is the highest-value test gap in the entire codebase. Never let the catch-all swallow parse errors silently in tests; consider re-throwing in test environments.

**Warning signs:**
- No `OpenAI::fake()` or `Http::fake()` call in any test file.
- `composer test` passes with `OPENAI_API_KEY` unset (means parser errors are being swallowed).
- `InspectionParserService` test coverage at 0%.

**Phase:** AI Wiring (Phase 1). Must be addressed before wiring the parser, not after.

---

### Pitfall 4: Whisper File Upload Hits PHP/Laravel/Nginx Upload Limits Before OpenAI Limits

**What goes wrong:** OpenAI Whisper accepts audio files up to 25 MB. A typical beekeeping inspection recording (2–5 minutes of voice) is 1–8 MB as MP3/M4A, well within Whisper's limit. However, PHP's `upload_max_filesize` and `post_max_size` default to 2 MB and 8 MB respectively. Nginx's `client_max_body_size` defaults to 1 MB. A user uploading a 4-minute recording hits a hard server error before the request reaches Laravel — with no useful error message shown in the UI.

**Why it happens:** Livewire file uploads go through Laravel's standard PHP upload handling. The defaults are set for web forms, not audio files. This is easy to miss because it works fine in local dev (where `php artisan serve` has relaxed limits) but fails in production.

**Consequences:**
- Silent 413 error or Livewire returns a generic network error.
- The user assumes the feature is broken and stops using audio input.
- Debugging is non-obvious because the failure happens at the web server layer, not in PHP or Laravel.

**Prevention:** Set `upload_max_filesize = 25M`, `post_max_size = 26M` in `php.ini` (or `.user.ini`). If using Nginx, set `client_max_body_size 26M` in the server block. Add a Livewire `rules` validation for `mimes:mp3,mp4,m4a,wav,webm` and `max:25600` (KB) so the UI gives a clear error if the file is too large, before the upload attempt reaches the server. Document these environment requirements.

**Warning signs:**
- Audio upload works locally but returns a generic error in production.
- Browser network tab shows 413 status.
- No file size validation exists in the Livewire component.

**Phase:** Audio transcription phase. Set limits before building the upload UI.

---

### Pitfall 5: Whisper Transcription Returns Raw Spoken Audio, Not Beekeeping Notes

**What goes wrong:** Whisper converts speech to text accurately, but a beekeeper speaking during an inspection says things like "uhh, let me check... frame four... yeah queen's there, good pattern, maybe a three?" The raw transcript is not the same as typed notes. If this transcript is passed directly to `InspectionParserService::parseRaw()`, the parser will attempt to extract fields from fragmented, non-linear, filler-word-heavy text.

**Why it happens:** The temptation is to treat Whisper output as equivalent to typed notes — pipe transcript straight into `raw_notes` and call `parseRaw()`. But `gpt-4o-mini` in the current system prompt is optimized for written observations, not spoken transcripts with hesitations, self-corrections, and incomplete sentences.

**Consequences:**
- AI extracts fewer fields from spoken transcripts than from written notes, generating more follow-up questions than expected.
- Frame counts and scores are particularly unreliable when spoken ("a three or maybe four" → ambiguous).
- The keeper sees a worse experience from audio than from typing, defeating the feature's purpose.

**Prevention:** Add a cleaning/normalization step between Whisper output and the parser — either a second GPT call to clean up the transcript into structured prose, or tune the system prompt to handle spoken-style input explicitly. At minimum, show the raw transcript to the keeper before parsing so they can edit it. Do not auto-parse a raw Whisper transcript without keeper review.

**Warning signs:**
- Transcript is passed directly to `parseRaw()` without display or editing step.
- Follow-up questions count is significantly higher for audio-originated inspections than typed ones.

**Phase:** Audio transcription phase. Design the transcript-to-parse handoff before implementing it.

---

### Pitfall 6: Overwriting User Edits with Stale AI Values

**What goes wrong:** The `updatedRawNotes()` handler unconditionally sets all AI-returned fields on the Livewire component using `array_key_exists` guards only. If the keeper types notes, sees the AI fill in a `broodPatternScore` of 3, manually changes it to 4, then adds one more sentence to their notes, the next AI parse fires (after the 5-second debounce) and resets `broodPatternScore` back to 3 — silently overwriting the keeper's correction.

**Why it happens:** The component has no memory of which fields were user-edited vs. AI-filled. Every parse result is applied wholesale.

**Consequences:**
- Keeper corrections are lost without any warning.
- The keeper cannot trust the form to hold their edits while they continue writing notes.
- This is a UX-destroying bug that only appears after the keeper has been using the feature for a few minutes.

**Prevention:** Track which fields have been manually edited using a `$manuallyEdited = []` array (or a simple dirty-flag per field). In `updatedRawNotes()`, skip AI values for fields in `$manuallyEdited`. Clear the flag for a field when the AI's parse is the first value (field was empty before). Implement `updated{FieldName}()` lifecycle hooks or a `$dirty` tracking set for each structured field.

**Warning signs:**
- No `$manuallyEdited` or equivalent tracking on the component.
- All fields in `updatedRawNotes()` are set unconditionally from AI response.

**Phase:** AI Wiring (Phase 1). Must be designed before the parse integration is shipped.

---

## Moderate Pitfalls

These cause noticeable friction or technical debt, but not immediate breakage.

---

### Pitfall 7: Hard-Coded Model String Cannot Be Overridden for Testing

**What goes wrong:** `'model' => 'gpt-4o-mini'` is hard-coded in `InspectionParserService`. There is no `OPENAI_MODEL` env variable. When you want to run integration tests against a cheaper/faster model (or `gpt-4o` for higher accuracy), it requires a code change.

**Prevention:** Move to `config('services.openai.model', 'gpt-4o-mini')` and add `OPENAI_MODEL` to `.env.example`. This also allows staging environments to use a different model than production without code changes.

**Phase:** AI Wiring (Phase 1). One-line fix, high leverage.

---

### Pitfall 8: `(bool)` and `(int)` Casts Are Fragile Against GPT Output Variation

**What goes wrong:** `extractFields()` casts with `(bool)` and `(int)` directly. These work when GPT returns `true`/`false`/`1`/`0` as JSON types, but GPT occasionally returns string `"true"`, `"yes"`, `"3 frames"`, or `null` where an integer is expected. `(int)"3 frames"` returns `3` (accidentally correct), but `(int)"a few"` returns `0`, silently writing a zero to the database where the field should be null.

**Prevention:** Add explicit type validation after casting: check `is_bool($v)` before treating as boolean, use `is_numeric()` before casting to int. A dedicated `safeInt()` / `safeBool()` method on the service that returns null on unexpected input is safer than direct casts. Add unit tests covering edge cases: `"true"`, `"yes"`, `null`, `""`, `"3-4"`.

**Phase:** AI Wiring (Phase 1). Add unit tests for `extractFields()` before wiring parsing end-to-end.

---

### Pitfall 9: Disease List in `extractFields()` Can Drift from the System Prompt

**What goes wrong:** The valid disease list `['Chalkbrood', 'Sacbrood', 'EFB', ...]` is defined in two separate places: the system prompt (as an enumeration of allowed values) and the `$validDiseases` array in `extractFields()`. If someone adds a new disease to the system prompt but forgets to update `$validDiseases`, GPT will return the new value but `array_intersect` will silently drop it.

**Prevention:** Extract the disease list to a single source of truth — a constant on the `Inspection` model or a dedicated enum. Reference the constant in both `systemPrompt()` and `extractFields()`. Add a unit test that asserts the system prompt contains every value in the valid disease array.

**Phase:** AI Wiring (Phase 1). Low-effort refactor with outsized reliability benefit.

---

### Pitfall 10: Charts Show Misleading Trends When Null Fields Are Treated as Zero

**What goes wrong:** Most inspection fields are nullable (the keeper may not record varroa count, brood pattern score, etc. on every inspection). Chart libraries default to connecting data points with lines. If nulls are passed as `0` to the chart, a "no data" inspection appears as a sudden drop to zero — making it look like the colony's health crashed when in fact the keeper just didn't record that metric that day.

**Why it happens:** The naive approach is `$inspections->pluck('varroa_count')` → pass to chart. SQLite returns null as `null`, which many JS chart libraries treat as `0` or connect across to the next point. The distinction between "not measured" and "zero mites" is critical for varroa counts specifically.

**Consequences:**
- A keeper could interpret a chart as showing a varroa emergency when the count was simply not taken.
- For varroa counts, "0" means "clean hive" — a very different interpretation from "not measured."

**Prevention:** Filter null values from chart series and use the chart library's "gap" or "null handling" configuration to visually break the line at missing data points rather than interpolating. Label the gap clearly. This is especially important for varroa counts. Query should always exclude nulls for chart rendering: `->whereNotNull('varroa_count')`.

**Phase:** Charts/trends phase. Design null handling before picking a chart library — not all libraries support gaps equally well.

---

### Pitfall 11: No Rate Limiting Leaves OpenAI Cost Exposure Open

**What goes wrong:** The current `updatedRawNotes()` hook fires after every 5-second pause. For a user who types a long notes session (say, 10 minutes), this could be 20+ API calls in one sitting. Multiply by `$0.00015/1K tokens` for gpt-4o-mini input and the cost is still trivial per user — but if the app is shared publicly (stated in PROJECT.md), even a small number of active users without rate limits represents unbounded cost exposure.

**Prevention:** Add Laravel's built-in rate limiter to the parse action: `RateLimiter::attempt('inspection-parse:' . auth()->id(), 10, fn() => ..., 60)` — limit to 10 parses per user per minute. This is a one-liner in the Livewire action. For the "shared publicly" scenario, add a daily cap per user as well.

**Phase:** AI Wiring (Phase 1). Add at the same time as wiring the parser, not as an afterthought.

---

## Minor Pitfalls

---

### Pitfall 12: `followup_questions` Set to `null` vs. `[]` Inconsistency

**What goes wrong:** `extractFields()` sets `followup_questions` to `null` when there are no questions. The Livewire component initializes `$followupQuestions` as `[]`. The blade template checks `!empty($followupQuestions)`. This is consistent now, but if anyone writes a query like `->whereNotNull('followup_questions')` to find "inspections that need follow-up," they'll miss inspections where questions were asked and then resolved (set back to null vs. empty array).

**Prevention:** Pick one convention and document it: `null` means "never parsed" / `[]` means "parsed, no questions." Enforce it with a cast and a comment in the model.

**Phase:** AI Wiring (Phase 1). Decide before wiring.

---

### Pitfall 13: Multi-User Authorization Via Query Scoping Only (No Policies)

**What goes wrong:** All authorization is done by scoping Eloquent queries to `auth()->id()` and using `findOrFail`. This is correct today and tested. But as new features are added (e.g., sharing an inspection history chart, future export endpoints), each new feature must remember to apply the same `user_id` scoping manually. There are no policy classes to enforce this at the model level.

**Prevention:** For the current personal-tool use case this is acceptable. The risk increases if any read endpoint is added that doesn't go through a Livewire component (e.g., a chart data API endpoint for an Alpine.js chart). Any new HTTP endpoint must be treated as a potential scope bypass. Consider a global scope on `Inspection` that automatically filters by `auth()->id()` when authenticated.

**Phase:** Charts/trends phase, if chart data is served via a separate route. Low priority for current scope.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|----------------|------------|
| AI Wiring — wire parse to save/input | Pitfalls 1, 2, 6 — trigger design, dead job, overwrite | Design explicit parse trigger first; delete dead job |
| AI Wiring — test coverage | Pitfall 3 — real API calls in tests | Fake OpenAI client before writing any new parse code |
| AI Wiring — parser robustness | Pitfalls 7, 8, 9 — model config, fragile casts, list drift | Unit tests for `extractFields()` with edge-case fixtures |
| Audio upload — Whisper | Pitfall 4 — server upload limits | Set PHP/Nginx limits; document in deployment notes |
| Audio upload — transcript quality | Pitfall 5 — raw transcript fed to parser | Show transcript to user before parsing; prompt handles spoken style |
| Charts/trends | Pitfall 10 — nulls as zeros | Design null handling per-series; use gap-aware chart library |
| Any new phase | Pitfall 11 — rate limiting | Add `RateLimiter` at AI Wiring phase; carry forward |

---

## Resolved Concerns (Previously Flagged, Now Verified)

The following concern from `CONCERNS.md` was verified against the migration and model:

**`supers_added` / `supers_removed` not in schema** — RESOLVED. Both columns ARE present in the migration (`unsignedTinyInteger`, nullable) and in the model `#[Fillable]` attribute. Data is not being dropped. No action needed.

---

## Sources

- Direct codebase analysis: `app/Services/InspectionParserService.php`, `app/Jobs/ParseInspectionNotes.php`, `resources/views/livewire/pages/inspections/create.blade.php`, `app/Models/Inspection.php`, `database/migrations/2026_04_02_102712_create_inspections_table.php`, `tests/Feature/InspectionTest.php`
- Project context: `.planning/PROJECT.md`, `.planning/codebase/CONCERNS.md`
- OpenAI Whisper docs (training knowledge, MEDIUM confidence): 25 MB file limit, supported formats
- Laravel rate limiter (training knowledge, HIGH confidence): `RateLimiter::attempt()` API
- openai-php/laravel fake client (training knowledge, MEDIUM confidence): `OpenAI::fake()` test helper
