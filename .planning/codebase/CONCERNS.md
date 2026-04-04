# Concerns

## Technical debt

### Dead code — `ParseInspectionNotes` job
- **File:** `app/Jobs/ParseInspectionNotes.php`
- **Issue:** Queue job created but never dispatched anywhere in the codebase. The inspection create/edit flows call `InspectionParserService` synchronously rather than via queue.
- **Risk:** Confusion about intended dispatch pattern; dead code creates maintenance overhead.
- **Resolution:** Either wire up dispatch or delete the job if synchronous parsing is the chosen approach.

### Hard-coded OpenAI model
- **File:** `app/Services/InspectionParserService.php:22`
- **Issue:** `'model' => 'gpt-4o-mini'` is hard-coded; no env-based override (`OPENAI_MODEL` or similar).
- **Risk:** Cannot switch models without a code change; no way to test with a cheaper/stub model.

### No error handling for OpenAI failures
- **File:** `app/Services/InspectionParserService.php`
- **Issue:** `parseRaw()` has no try/catch around the API call. A network failure, rate limit, or API error will bubble up as an unhandled exception to the Livewire component.
- **Risk:** Silent failure or ugly error page for the user; no retry or degradation path.

### `supers_added` / `supers_removed` in parser but not in model
- **File:** `app/Services/InspectionParserService.php:109`, `app/Models/Inspection.php`
- **Issue:** Parser extracts `supers_added` and `supers_removed` fields, but the `Inspection` model's `$fillable` may not include them and the migration may not have these columns.
- **Risk:** Silently dropped data after AI parsing.

## Testing gaps

### `InspectionParserService` has zero test coverage
- The most complex and critical business logic in the app (AI field extraction, type coercion, disease validation) has no unit tests.
- JSON parsing edge cases, null handling, and `extractFields()` logic are all untested.

### No mock/stub for OpenAI in feature tests
- Feature tests that exercise the inspection create flow (`can create an inspection with only required fields`) don't appear to trigger AI parsing — but if parsing is wired in, tests would make real API calls or fail without credentials.

## Security

### No rate limiting on AI parsing
- If AI parsing is triggered on every save, a user could spam the endpoint and rack up OpenAI costs. No rate limiting or debouncing is visible.

### SQLite in production
- `.env` / `config/database.php` defaults to SQLite. Acceptable for development but a concern if this ever goes to production without a migration to PostgreSQL/MySQL.

## Performance

### Synchronous AI parsing blocks the request
- `InspectionParserService::parse()` makes a blocking HTTP call to OpenAI within the Livewire save action. This adds 1-5+ seconds of latency to every inspection save.
- The `ParseInspectionNotes` job exists to solve this but is never used.

## Fragile areas

### `extractFields()` type-cast assumptions
- `(bool)`, `(int)` casts on AI-returned values work for clean JSON but are fragile if OpenAI returns unexpected types (e.g., string `"true"` for a boolean).

### Disease validation via `array_intersect`
- Valid disease list is defined inline in `extractFields()`. If the system prompt and this list drift, diseases could be silently filtered.

### Multi-user isolation relies on Eloquent scoping only
- Authorization is enforced by scoping queries to `auth()->id()` and using `findOrFail`. No policy classes or gates — straightforward but must be maintained consistently as new features are added.
