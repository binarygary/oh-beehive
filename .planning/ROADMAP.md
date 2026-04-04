# Roadmap: Oh Beehive

## Overview

This milestone completes the core value proposition of the app in three capability layers: first, wiring AI parsing end-to-end with reliable provenance tracking and test coverage; second, adding audio upload and in-browser recording so keepers can speak their observations instead of typing them; third, surfacing inspection history as trend charts per hive. Each layer depends on the one before it — audio transcription feeds the same parse flow established in the first phases, and charts visualize data that only accrues once parsing is trustworthy.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Parser Foundation** - Delete dead queue job, register test stub, close critical pitfalls before any new feature work
- [ ] **Phase 2: Field Provenance** - Track which fields were AI-filled, visually distinguish them, protect manual edits from re-parse overwrites
- [ ] **Phase 3: Interactive Follow-Up Q&A** - Keeper answers AI follow-up questions inline; answers appended to raw notes and trigger re-parse
- [ ] **Phase 4: Audio Upload and Transcription** - Upload MP3/M4A/WAV, transcribe via Whisper, populate raw_notes, trigger parse pipeline
- [ ] **Phase 5: In-Browser Audio Recording** - Record audio directly in the browser via MediaRecorder, transcribe, auto-populate raw_notes
- [ ] **Phase 6: Inspection History List** - Hive detail page shows all inspections for that hive, scoped by user
- [ ] **Phase 7: Trend Charts** - Line charts for health score, varroa count, brood pattern, and frames of bees over time per hive

## Phase Details

### Phase 1: Parser Foundation
**Goal**: The AI parse flow is wired synchronously, dead code is removed, and tests never make real API calls
**Depends on**: Nothing (existing codebase; this is the first phase)
**Requirements**: PARSE-01, PARSE-08
**Note**: PARSE-02, PARSE-03, PARSE-04, PARSE-06 are already satisfied by existing code and are not work items in this roadmap.
**Success Criteria** (what must be TRUE):
  1. Keeper types notes and clicks a parse trigger; structured fields are populated without saving
  2. The `ParseInspectionNotes` queue job file no longer exists in the codebase
  3. Running `composer test` does not make any real OpenAI API calls (all tests pass with a faked/stubbed client)
  4. A failed OpenAI call shows a non-blocking notice on the form rather than silently swallowing the error
**Plans**: TBD

### Phase 2: Field Provenance
**Goal**: Keeper can see which fields were filled by AI, and manual edits to those fields are preserved across re-parses
**Depends on**: Phase 1
**Requirements**: PARSE-05
**Success Criteria** (what must be TRUE):
  1. Fields populated by AI have a visible visual indicator (e.g., badge or highlight) distinguishing them from manually entered fields
  2. A field the keeper has manually edited is not overwritten when a re-parse runs
  3. A field the keeper has not touched is updated by re-parse as expected
**Plans**: TBD
**UI hint**: yes

### Phase 3: Interactive Follow-Up Q&A
**Goal**: Keeper can answer AI follow-up questions directly on the inspection form without switching context
**Depends on**: Phase 2
**Requirements**: PARSE-07
**Success Criteria** (what must be TRUE):
  1. AI follow-up questions appear as editable input fields on the form (not read-only bullets)
  2. After a keeper types an answer, that answer is appended to raw_notes and a re-parse is triggered
  3. Re-parsed fields reflect the new information from the answered question
  4. After re-parse, questions that were resolved no longer appear (or are visually dismissed)
**Plans**: TBD
**UI hint**: yes

### Phase 4: Audio Upload and Transcription
**Goal**: Keeper can upload a recorded audio file and have it transcribed into raw_notes, then parsed into structured fields
**Depends on**: Phase 1
**Requirements**: AUDIO-01, AUDIO-03, AUDIO-05, AUDIO-06
**Success Criteria** (what must be TRUE):
  1. Keeper can select and upload an MP3, M4A, or WAV file from the inspection form
  2. A loading indicator is shown for the full duration of transcription (which may take 10-20 seconds)
  3. After transcription completes, the transcript text appears in the raw_notes field
  4. The parse pipeline runs automatically on the transcript, populating structured fields
  5. No audio file is stored on the server after transcription completes
**Plans**: TBD
**UI hint**: yes

### Phase 5: In-Browser Audio Recording
**Goal**: Keeper can record audio directly in the browser without a separate app or file management step
**Depends on**: Phase 4
**Requirements**: AUDIO-02, AUDIO-04
**Success Criteria** (what must be TRUE):
  1. Keeper can start and stop an audio recording from within the inspection form in the browser
  2. After stopping, the recording is transcribed via the same Whisper pipeline as file uploads
  3. The transcript populates raw_notes and the parse pipeline runs automatically
  4. The recording state (idle / recording / transcribing) is clearly communicated to the keeper
**Plans**: TBD
**UI hint**: yes

### Phase 6: Inspection History List
**Goal**: Keeper can browse all inspections for a hive in one place, ordered by date
**Depends on**: Phase 1
**Requirements**: CHART-01
**Success Criteria** (what must be TRUE):
  1. Navigating to a hive shows a list of all past inspections for that hive
  2. Each inspection entry shows the date and overall health score
  3. Inspections are scoped to the authenticated user (no cross-user data visible)
  4. Keeper can click an inspection entry to open it
**Plans**: TBD
**UI hint**: yes

### Phase 7: Trend Charts
**Goal**: Keeper can see how a hive has changed over time through line charts for key metrics
**Depends on**: Phase 6
**Requirements**: CHART-02, CHART-03, CHART-04, CHART-05, CHART-06
**Success Criteria** (what must be TRUE):
  1. The hive detail page shows a line chart for overall health score across all inspections
  2. The hive detail page shows a line chart for varroa count; inspections with no varroa count recorded appear as gaps in the line, not as zero
  3. The hive detail page shows a line chart for brood pattern score across all inspections
  4. The hive detail page shows a line chart for frames of bees across all inspections
  5. All charts are rendered client-side via Chart.js initialized through Alpine.js `x-init`; the chart canvas is protected from Livewire DOM morphing
**Plans**: TBD
**UI hint**: yes

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7

Note: Phase 4 and Phase 6 both depend only on Phase 1 and are independent of each other. If chart visualization is higher priority, Phase 6 → Phase 7 can execute before Phase 4 → Phase 5. The recommended order above prioritizes the audio path first.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Parser Foundation | 0/? | Not started | - |
| 2. Field Provenance | 0/? | Not started | - |
| 3. Interactive Follow-Up Q&A | 0/? | Not started | - |
| 4. Audio Upload and Transcription | 0/? | Not started | - |
| 5. In-Browser Audio Recording | 0/? | Not started | - |
| 6. Inspection History List | 0/? | Not started | - |
| 7. Trend Charts | 0/? | Not started | - |
