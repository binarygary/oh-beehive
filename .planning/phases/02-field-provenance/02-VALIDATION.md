---
phase: 02
slug: field-provenance
status: approved
nyquist_compliant: true
wave_0_complete: true
created: 2026-04-06
---

# Phase 02 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4.x + Livewire Volt testing helpers |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `./vendor/bin/pest tests/Feature/InspectionCreateTest.php tests/Feature/InspectionEditTest.php` |
| **Full suite command** | `composer test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `./vendor/bin/pest tests/Feature/InspectionCreateTest.php tests/Feature/InspectionEditTest.php`
- **After every plan wave:** Run `composer test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | PARSE-05 | T-02-01 / T-02-03 | Create-form provenance keys only track explicit parser-owned fields and clear on keeper override | feature | `./vendor/bin/pest tests/Feature/InspectionCreateTest.php` | ❌ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | PARSE-05 | T-02-01 / T-02-02 / T-02-03 | Create-form labels render static `AI` badges only for current parser-owned fields | feature | `./vendor/bin/pest tests/Feature/InspectionCreateTest.php` | ❌ W0 | ⬜ pending |
| 02-02-01 | 02 | 2 | PARSE-05 | T-02-04 / T-02-06 | Edit-form provenance overlays saved inspections without persisting parser ownership and clears on keeper override | feature | `./vendor/bin/pest tests/Feature/InspectionEditTest.php` | ❌ W0 | ⬜ pending |
| 02-02-02 | 02 | 2 | PARSE-05 | T-02-04 / T-02-05 / T-02-06 | Edit-form labels render static `AI` badges only for the latest parse payload | feature | `./vendor/bin/pest tests/Feature/InspectionEditTest.php` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/InspectionCreateTest.php` — create-form provenance tests for parse population, per-field clearing, and re-parse refresh
- [ ] `tests/Feature/InspectionEditTest.php` — edit-form provenance tests for parse population, per-field clearing, and re-parse refresh
- [ ] Existing Pest/Volt infrastructure covers the rest of the phase; no framework install is required

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Provenance badge clears at blur/change time in the browser | PARSE-05 | `Volt::test()` proves server-side state transitions but not the exact client sync feel of `wire:model.blur` and `wire:model.change` | Open create and edit inspection forms, run Parse, change one parser-owned text/select field, and confirm only that field’s `AI` badge disappears immediately after blur/change without Save |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** approved 2026-04-06
