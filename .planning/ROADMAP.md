# Roadmap: Oh Beehive

## Phases

- [ ] **Phase 1: Parser Foundation**
- [ ] **Phase 2: Field Provenance**

## Phase Details

### Phase 1: Parser Foundation
**Goal:** Wire InspectionParserService end-to-end: delete dead queue job, bind interface in container, add fake for tests, add manual Parse trigger to UI.
**Plans**:
- [x] 01-01-PLAN.md
- [x] 01-02-PLAN.md
- [x] 01-03-PLAN.md

### Phase 2: Field Provenance
**Goal:** Visually distinguish AI-filled fields from manually entered fields on the inspection form so the keeper knows which values came from the parser and which they entered themselves.
**Requirements:** PARSE-05
**Plans**: 2 plans
Plans:
- [ ] 02-01-PLAN.md — Add create-form AI provenance state, badge rendering, and focused Volt coverage
- [ ] 02-02-PLAN.md — Mirror AI provenance on the edit form with isolated Volt coverage
**Canonical refs:**
- `.planning/REQUIREMENTS.md` §AI Parsing — PARSE-05 definition
- `resources/views/livewire/pages/inspections/` — inspection form Volt components
