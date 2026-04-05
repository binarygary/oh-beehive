# Discussion Log: Phase 1 — Parser Foundation

**Date:** 2026-04-04
**Mode:** Interactive discuss-phase

---

## Areas Selected

All four areas selected by user: Parse trigger, Error notice style, OpenAI test stub approach, Test coverage scope.

---

## Parse Trigger

**Q:** SC-1 says "clicks a parse trigger" — does Phase 1 need an explicit Parse button, or does the existing auto-debounce satisfy this requirement?
**A:** Add explicit Parse button

**Q:** Where should the Parse button sit relative to the raw notes textarea?
**A:** Below the textarea

**Q:** Should the "Analyzing…" spinner stay tied to the button click, and should auto-debounce be removed entirely?
**A:** Button only — remove debounce entirely

---

## Error Notice Style

**Q:** When OpenAI fails, what should the keeper see?
**A:** Alert under the textarea (DaisyUI alert-warning, below notes/button area)

**Q:** Should the error alert clear automatically on next successful parse, or stay until dismissed?
**A:** Auto-clears on next successful parse

---

## OpenAI Test Stub Approach

**Q:** How should the OpenAI client be faked in tests?
**A:** Extract interface + fake implementation (`InspectionParserInterface` with `parseRaw()` and `parse()`)

**Q:** Should the interface be minimal (just parseRaw + parse) or mirror full service?
**A:** Minimal — just parseRaw() and parse()

---

## Test Coverage Scope

**Q:** Should Phase 1 add dedicated unit tests for InspectionParserService field extraction logic?
**A:** Yes — unit test extractFields logic

**Q:** What should the unit tests cover?
**A:** All four areas selected:
- Field extraction happy path
- Null/missing field handling
- Invalid disease filtering
- Empty/short notes guard
