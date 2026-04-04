# Oh Beehive

## What This Is

A personal beekeeping inspection app that turns free-form notes (typed or recorded audio) into structured inspection records using AI. The keeper types or speaks their observations; the AI parses them into structured fields, asks follow-up questions for anything it couldn't resolve, and the keeper reviews and confirms before saving. May be shared publicly for free.

## Core Value

A beekeeper can record an inspection in natural language and get a complete, structured record without manually filling in fields.

## Requirements

### Validated

- ✓ User authentication (login/logout, session management) — existing
- ✓ Hive CRUD (create, edit, delete, list with status/location/notes) — existing
- ✓ Inspection form with full structured field set (all domain fields present) — existing
- ✓ Multi-user data isolation (all hives and inspections scoped to user_id) — existing
- ✓ AI parser service extracts structured fields from raw_notes via OpenAI — existing
- ✓ Follow-up questions stored in followup_questions JSON column — existing

### Active

- [ ] AI parsing wired end-to-end (currently InspectionParserService exists but is never called on save)
- [ ] Review + confirm flow: AI fills structured fields, keeper reviews/adjusts before saving
- [ ] Follow-up questions shown inline on the inspection form, keeper answers before saving
- [ ] Audio upload → transcription → AI parse (upload a recording, get structured fields)
- [ ] Inspection history per hive with trends over time (charts: health scores, varroa counts, frame counts across inspections)

### Out of Scope

- User self-registration — accounts created via artisan only; single-keeper personal tool
- Real-time live dictation — audio upload is sufficient, real-time adds complexity without clear value
- Paid tiers / monetization — will be free if shared publicly
- Hive type selection — all Langstroth, no type field needed
- Treatment as structured field — free text is sufficient

## Context

- **Existing codebase:** Laravel 13, Livewire v3 + Volt, Tailwind v4 + DaisyUI v5, SQLite dev
- **AI integration partially built:** `InspectionParserService` parses raw notes via OpenAI gpt-4o-mini, returns structured fields. A `ParseInspectionNotes` queue job exists but is never dispatched — the service is not yet called on inspection save.
- **OpenAI client** registered as singleton in AppServiceProvider via `openai-php/laravel`
- **Parsing flow** confirmed: raw notes → OpenAI JSON → field extraction + type coercion → `followup_questions` array
- **Audio path** not yet built — will need transcription (OpenAI Whisper) before parsing

## Constraints

- **Tech stack:** Laravel 13 + Livewire v3 + Volt — no framework changes
- **Database:** SQLite for dev/personal use; not designed for scale
- **AI provider:** OpenAI (gpt-4o-mini for parsing, Whisper for audio transcription)
- **No registration:** Users created only via `php artisan make:user`
- **Code quality:** GrumPHP enforces Larastan level 5 + Pint + Pest on every commit; `declare(strict_types=1)` required in all `app/` files

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Synchronous AI parsing (not queued) | Simpler for single-user personal tool; queue job exists but unused | — Pending |
| gpt-4o-mini for field parsing | Cost-effective; structured JSON output reliable for this domain | — Pending |
| OpenAI Whisper for audio transcription | Same provider as parsing; avoids additional service dependency | — Pending |
| Free text for treatment_applied | Treatments too varied to enumerate usefully | — Pending |
| SQLite for storage | Personal tool, single user, no concurrency concerns | — Pending |

---
*Last updated: 2026-04-04 after initialization*

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state
