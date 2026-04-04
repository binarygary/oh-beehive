# Feature Landscape

**Domain:** Personal beekeeping inspection app with AI-assisted data capture
**Researched:** 2026-04-04
**Confidence:** HIGH (based on code inspection of existing app + domain reasoning; web search unavailable)

---

## Context: Where the App Stands

The app has a complete data model and a working inspection form. The AI parser (`InspectionParserService`) already fires on `updatedRawNotes` (5-second debounce) and fills fields live. Follow-up questions are already stored and rendered as a warning block. What is NOT yet working:

- The review/confirm step is implicit — AI fills fields, user can modify, user clicks Save. There is no explicit "AI parsed — does this look right?" state.
- Follow-up questions are displayed as read-only bullets. Users cannot answer them inline.
- Audio upload does not exist. The form only accepts typed text.
- Inspection history per hive has no charting or trend view.

All four milestone features are incremental improvements to a working foundation, not net-new systems.

---

## Table Stakes

Features without which the app fails its core promise ("type notes, get structured record").

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| AI parsing wired end-to-end on save | Core value prop — parser exists but save flow doesn't guarantee a parse has happened (user might bypass debounce by saving immediately) | Low | Trigger `parseRaw` on save if no parse has fired, or on every save. Synchronous is fine given single-user, personal tool. |
| Parsed fields visible before save | User must be able to see what AI extracted before committing — otherwise they cannot trust or correct the output | Low | Already partly done: fields populate live. The gap is clarity: user needs to know "these values came from AI" vs "I typed them". |
| AI-sourced field indicators | Without visual differentiation, AI-filled vs manually-entered fields are indistinguishable. Keeper cannot tell what needs review. | Low | A subtle badge or icon on AI-filled fields (e.g., a small "AI" chip or different border colour) lets the keeper scan quickly for values to verify. Clear on manual edit. |
| Follow-up questions actionable | Displaying follow-up questions as read-only bullets (current state) is half-done. The keeper needs to know they can add answers to their notes, or the questions feel like dead ends. | Low | Current UI says "add details to your notes to fill these in" — this is the right instruction. Ensure it is prominent and re-parses after the user does so. |
| Graceful AI failure handling | Parser has no try/catch (noted in CONCERNS.md). A network error or rate limit should degrade gracefully, not crash the form. | Low | Already has a bare try/catch in the Livewire component (`updatedRawNotes`), but it silently swallows errors. Should show a non-blocking notice: "AI parsing unavailable — fill in fields manually." |
| Inspection list per hive | User needs to browse past inspections for a given hive. A flat global list without hive-scoping is inadequate for multi-hive use. | Low | Filter existing inspection index by hive_id. |

---

## Differentiators

Features that make this meaningfully better than a paper record or a manual form.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Audio upload → transcription → parse | Hands-free recording during/after inspection. Voice is faster than typing when wearing gloves or in a suit. Whisper transcription + existing parser = complete pipeline with one new step. | Medium | File upload (Livewire `WithFileUploads`) → `openai()->audio()->transcribe()` → existing `parseRaw()`. Storage: local disk is fine for personal use. Max file size: Whisper accepts up to 25MB; 1-3 min recordings are typically 2-5MB as MP3. Process synchronously (personal tool, no concurrency). |
| Inline follow-up question answers | Instead of asking the keeper to re-phrase notes, show follow-up questions as editable inline prompts. Keeper types short answers directly. Answers get appended to raw_notes and re-parsed. | Medium | UI: each question becomes a small labeled text input below the question text. On answer submission, append "Q: [question] A: [answer]" to raw_notes and re-trigger parse. Avoids requiring the keeper to mentally restructure their prose. |
| Inspection history with trend charts | Over time, the most valuable output is trajectory: is varroa load increasing? Is brood pattern declining? Charts answer questions a list of records cannot. | Medium | Key metrics to chart: overall_health_score, varroa_count, brood_pattern_score, frames_of_bees, frames_of_honey. Chart type: line chart over time per hive. Library recommendation: Chart.js via a Blade/Alpine component (lightweight, no React needed, good Livewire compatibility). Apexcharts is also viable. Do NOT use a full JS framework just for charts. |
| AI field provenance (which fields were AI-filled) | Keeper can see at a glance which fields the AI extracted vs which they set manually, and override with confidence. | Low | Store a `ai_filled_fields` JSON column (array of field names) on the inspection. Populated during parse. Cleared for individual fields when manually edited. Surface as subtle visual indicators on the form. |
| Re-parse on edit | When editing an existing inspection, if the keeper updates raw_notes, re-run parse and merge new extractions without overwriting manually-edited fields. | Medium | Requires knowing which fields were manually edited (see provenance above). Without provenance, re-parsing risks clobbering manual corrections. |
| Hive summary card with last-inspection data | Dashboard hive list shows last inspection date, last overall health score, last varroa count. Keeper sees hive health at a glance without opening an inspection. | Low | Query: `hive->inspections()->latest('inspected_at')->first()`. Add to the hive index/card view. |
| Seasonal inspection reminders | Alert when a hive hasn't been inspected in N days (configurable: 7/14/21 days). | Low | Blade-level: compare `now()` to last inspection date. No background job needed. Show a badge on the hive card. |

---

## Anti-Features

Features to deliberately NOT build for this personal tool.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Real-time live dictation (WebSockets) | Adds infrastructure complexity (Echo, Reverb or Pusher) for marginal gain over audio upload. The keeper doesn't need word-by-word streaming. | Audio upload with async transcription covers the use case without the infrastructure. |
| User self-registration and accounts | This is a personal tool. Multi-tenant auth adds surface area (email verification, password reset, rate limiting, abuse prevention). | Keep `php artisan make:user`. If ever shared, add only after deliberate decision. |
| Structured treatment fields (dropdowns/doses) | Treatment space is extremely varied (oxalic acid, ApiLife Var, Apistan, formic acid, thymol, etc.) with country-specific legal differences. Enumerating them creates maintenance burden and false precision. | Free text `treatment_applied` is sufficient and already implemented. |
| Hive type selection | All hives are Langstroth. Adding type adds UI noise and branching field logic with no payoff. | Leave type absent. |
| Photo attachments | Useful for expert consultation but adds significant storage and display complexity. Out of proportion for a personal data capture tool. | If ever needed, treat as a separate future milestone. |
| Sharing / public inspection reports | Adds access control complexity (public URLs, share tokens) disproportionate to personal tool value. | Out of scope per PROJECT.md. |
| Swarm prediction / ML inference | Interesting but requires training data volume this app will never accumulate for a single user. | Document interesting trends via charts instead. |
| Push notifications / email reminders | Mobile push requires a service worker; email requires a mailer config. Blade-level "days since inspection" badges achieve 80% of the value. | In-app badges on hive cards. |
| Offline mode / PWA | SQLite + personal use means the keeper is presumably on the same network as the server or using it locally. Offline sync adds significant complexity. | Not needed. |
| Queued AI parsing | The `ParseInspectionNotes` job exists but adds complexity (queue worker, failed job handling, polling for completion) for a single-user tool where 1-5 second synchronous response is acceptable. | Delete the job. Parse synchronously. |

---

## Feature Dependencies

```
Audio upload → Whisper transcription → existing parseRaw() → field fill
                                                              |
                                                    Inline follow-up answers
                                                    (answers appended to raw_notes → re-parse)

AI field provenance (ai_filled_fields column)
  → Required for: safe re-parse on edit (know what not to overwrite)
  → Required for: AI field indicators in UI
  → Required for: clearing provenance on manual edit

Inspection history per hive (list view)
  → Required before: trend charts (need history to render)

Inspection list per hive → Hive summary card (derived from same query)
```

---

## MVP Recommendation for This Milestone

The four active requirements from PROJECT.md map to this priority order:

1. **AI parsing end-to-end with field provenance** — Wire the save flow to guarantee a parse has run. Add `ai_filled_fields` tracking. Show AI-source indicators on form fields. This is the foundation for everything else and the core value prop.

2. **Inline follow-up question answers** — Replace read-only question bullets with editable answer inputs. Append answers to raw_notes and re-parse. High value relative to implementation cost.

3. **Audio upload and transcription** — File upload → Whisper → parseRaw(). Synchronous. 25MB cap (Whisper limit). Accept common audio formats (MP3, M4A, WAV, OGG). Store transcription as raw_notes so the existing parse flow runs unchanged.

4. **Inspection history trends** — Per-hive chart page. Line charts for: overall_health_score, varroa_count, brood_pattern_score, frames_of_bees over time. Chart.js is the lowest-friction choice (CDN or npm, minimal setup, no framework). Defer complex multi-hive comparison or overlay views.

**Defer:**
- Re-parse on edit without provenance (risk of overwriting manual corrections — needs provenance feature first)
- Seasonal reminders (low complexity but not part of this milestone scope)
- Hive summary card last-inspection data (quick win, but not charting/trends)

---

## Complexity Notes by Feature

| Feature | Estimate | Key Complexity Driver |
|---------|----------|-----------------------|
| AI parse guaranteed on save | 1-2h | Decide sync vs skip-if-already-parsed; OpenAI error handling |
| AI field provenance column + UI indicators | 2-4h | Migration, model update, form badge rendering |
| Inline follow-up answers | 3-5h | Answer input UI, raw_notes append logic, re-parse trigger |
| Audio upload → transcription | 4-8h | Livewire `WithFileUploads`, Whisper API call, temp file cleanup, error states |
| Trend charts per hive | 4-8h | Chart.js integration, data query (inspections ordered by date per hive), responsive layout |

---

## Sources

- Codebase inspection: `app/Services/InspectionParserService.php`, `resources/views/livewire/pages/inspections/create.blade.php`
- Project context: `.planning/PROJECT.md`, `.planning/codebase/CONCERNS.md`
- Domain knowledge: beekeeping inspection practice, OpenAI Whisper API constraints (25MB limit, supported formats), Chart.js ecosystem familiarity
- Confidence: HIGH for features grounded in existing code; MEDIUM for chart library recommendation (Chart.js vs Apexcharts — both viable, either works, Chart.js has smaller bundle)
