# Requirements: Oh Beehive

**Defined:** 2026-04-04
**Core Value:** A beekeeper can record an inspection in natural language and get a complete, structured record without manually filling in fields.

## v1 Requirements

### AI Parsing

**Already validated (existing code):**
- ✓ **PARSE-02**: `InspectionParserService::parseRaw()` called on `updatedRawNotes` (5s debounce), fills all structured fields — existing
- ✓ **PARSE-03**: "Analyzing…" loading spinner shown during parse — existing
- ✓ **PARSE-04**: AI-filled fields are editable; keeper reviews and adjusts before saving — existing
- ✓ **PARSE-06**: Follow-up questions from AI shown inline on the inspection form — existing

**Still needed:**
- [ ] **PARSE-01**: Remove dead `ParseInspectionNotes` queue job (dead code, architectural ambiguity risk)
- [ ] **PARSE-05**: Fields filled by AI are visually distinguished from fields entered manually
- [ ] **PARSE-07**: Keeper can answer follow-up questions inline; answers appended to raw notes and trigger re-parse
- [x] **PARSE-08**: OpenAI client is faked/stubbed in tests so parsing tests don't make real API calls

### Audio Input

- [ ] **AUDIO-01**: Keeper can upload an audio file (MP3, M4A, WAV) on the inspection form
- [ ] **AUDIO-02**: Keeper can record audio directly in the browser on the inspection form
- [ ] **AUDIO-03**: Uploaded/recorded audio is transcribed via OpenAI Whisper
- [ ] **AUDIO-04**: Whisper transcript is placed into raw_notes and parsed into structured fields automatically
- [ ] **AUDIO-05**: A loading indicator is shown during transcription (10–20s for 2–3 min clips)
- [ ] **AUDIO-06**: Audio files are not permanently stored (ephemeral — transcribed then discarded)

### Inspection History & Trends

- [ ] **CHART-01**: Hive detail page shows inspection history list (date, overall health score)
- [ ] **CHART-02**: Overall health score trend chart per hive (score over time)
- [ ] **CHART-03**: Varroa count trend chart per hive (null values shown as gaps, never interpolated)
- [ ] **CHART-04**: Brood pattern score trend chart per hive
- [ ] **CHART-05**: Frames of bees trend chart per hive (population curve)
- [ ] **CHART-06**: Charts are rendered with Chart.js via Alpine.js `x-init` (no SSR chart library)

## v2 Requirements

### Multi-user API costs

- **MULTI-01**: Each user stores their own OpenAI API key in account settings (user brings their own key)
- **MULTI-02**: App uses the authenticated user's API key for all AI parsing and transcription calls

### Audio experience

- **AUDIO-07**: Show Whisper transcript to keeper for review/edit before parsing (reduces follow-up questions from spoken-language ambiguity)

### Hive management

- **HIVE-01**: Hive detail page shows summary stats (total inspections, last inspection date, average health score)

## Out of Scope

| Feature | Reason |
|---------|--------|
| User self-registration | Personal tool; accounts created via artisan only |
| Real-time live dictation | Audio upload is sufficient; streaming adds significant complexity |
| Paid tiers / monetization | Will be free if shared publicly |
| Hive type selection | All Langstroth, no type variation needed |
| Treatment as structured field | Free text is sufficient for keeper's needs |
| Transcript review before parsing | Deferred to v2; inline follow-up Q&A handles ambiguity from v1 |
| Persistent audio storage | Audio is transcribed then discarded; no storage needed |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| PARSE-02 | Existing (validated) | Complete |
| PARSE-03 | Existing (validated) | Complete |
| PARSE-04 | Existing (validated) | Complete |
| PARSE-06 | Existing (validated) | Complete |
| PARSE-01 | Phase 1 | Pending |
| PARSE-08 | Phase 1 | Complete |
| PARSE-05 | Phase 2 | Pending |
| PARSE-07 | Phase 3 | Pending |
| AUDIO-01 | Phase 4 | Pending |
| AUDIO-03 | Phase 4 | Pending |
| AUDIO-05 | Phase 4 | Pending |
| AUDIO-06 | Phase 4 | Pending |
| AUDIO-02 | Phase 5 | Pending |
| AUDIO-04 | Phase 5 | Pending |
| CHART-01 | Phase 6 | Pending |
| CHART-02 | Phase 7 | Pending |
| CHART-03 | Phase 7 | Pending |
| CHART-04 | Phase 7 | Pending |
| CHART-05 | Phase 7 | Pending |
| CHART-06 | Phase 7 | Pending |

**Coverage:**
- v1 requirements: 20 total (4 already validated from existing code)
- Remaining work items mapped to phases: 16/16
- Unmapped: 0 ✓

---
*Requirements defined: 2026-04-04*
*Last updated: 2026-04-04 after roadmap creation*
