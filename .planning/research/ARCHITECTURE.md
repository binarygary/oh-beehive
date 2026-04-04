# Architecture Patterns

**Domain:** Beekeeping inspection app — AI parsing, audio transcription, trend charts
**Researched:** 2026-04-04
**Overall confidence:** HIGH (based on codebase analysis + well-established library APIs)

---

## Existing Architecture Baseline

The app is a three-layer monolith:

```
Browser (Alpine.js + Livewire)
       |
Livewire Components (Volt, resources/views/livewire/pages/)
       |
Service Layer (app/Services/)
       |
OpenAI Client (singleton, AppServiceProvider)
       |
Eloquent Models (app/Models/)
       |
SQLite
```

Key constraint: **Livewire v3 components are server-side PHP with reactive properties.** Every user interaction that changes component state causes a network round-trip to the PHP server. This is the defining constraint for all integration decisions.

---

## Recommended Architecture

### Full System Component Map

```
┌─────────────────────────────────────────────────────────────┐
│  Browser                                                      │
│  ┌─────────────────────────┐  ┌────────────────────────────┐ │
│  │  Inspection Form        │  │  Hive History Page         │ │
│  │  (Livewire component)   │  │  (Livewire component)      │ │
│  │  - raw_notes textarea   │  │  - Alpine.js + Chart.js    │ │
│  │  - audio upload input   │  │  - data from PHP via       │ │
│  │  - parsed field display │  │    @js() / wire:init       │ │
│  │  - followup questions   │  └────────────────────────────┘ │
│  └──────────┬──────────────┘                                 │
└─────────────┼───────────────────────────────────────────────┘
              │ Livewire wire calls (HTTP POST)
┌─────────────▼───────────────────────────────────────────────┐
│  Livewire Layer (Volt single-file components)                 │
│                                                               │
│  inspections/create.blade.php                                 │
│  ├── WithFileUploads trait                                    │
│  ├── $audioFile (TemporaryUploadedFile)                       │
│  ├── parseAudio() action → TranscriptionService              │
│  ├── updatedRawNotes() → InspectionParserService             │
│  └── save() → Inspection::create()                           │
│                                                               │
│  hives/show.blade.php (new)                                   │
│  └── with() returns chart data arrays → @js() → Alpine+Chart │
└──────────┬──────────────────────────────────────────────────┘
           │
┌──────────▼──────────────────────────────────────────────────┐
│  Service Layer (app/Services/)                                │
│                                                               │
│  InspectionParserService (existing)                           │
│  └── parseRaw(string): array  — calls OpenAI chat()          │
│                                                               │
│  TranscriptionService (new)                                   │
│  └── transcribe(string $filePath): string                     │
│      — calls OpenAI audio()->transcribe()                     │
│      — returns transcript text                                │
│      — does NOT call parser (caller does that)               │
└──────────┬──────────────────────────────────────────────────┘
           │
┌──────────▼──────────────────────────────────────────────────┐
│  OpenAI Client (singleton, app/Providers/AppServiceProvider)  │
│  ├── chat()->create()  — gpt-4o-mini, JSON mode              │
│  └── audio()->transcribe()  — whisper-1                      │
└──────────┬──────────────────────────────────────────────────┘
           │
┌──────────▼──────────────────────────────────────────────────┐
│  Eloquent Models                                              │
│  ├── Inspection  (raw_notes, followup_questions, all fields)  │
│  └── Hive  (has many Inspections)                            │
└─────────────────────────────────────────────────────────────┘
```

---

## Component Boundaries

### Component 1: Inspection Create/Edit Volt Component

**File:** `resources/views/livewire/pages/inspections/create.blade.php` (and edit)

**Responsibilities:**
- Own all form state as Livewire properties
- Accept audio file upload via `WithFileUploads` trait
- Trigger transcription and parsing as sequential synchronous calls
- Populate form fields from parsed result
- Display follow-up questions inline
- Validate and persist on save

**What it does NOT do:**
- Call OpenAI directly — delegates to services
- Store the audio file permanently (temporary file only, discarded after transcription)
- Decide the transcription/parse logic — services own that

**Communicates with:**
- `TranscriptionService` — passes temp file path, receives transcript string
- `InspectionParserService` — passes transcript/raw notes string, receives field array
- `Inspection` model — creates/updates record
- `Hive` model — reads user's hives for select list

**Boundary rule:** The component maps service output to its own properties. Services return plain `array<string, mixed>`. No Eloquent models cross the service boundary into components except as IDs.

---

### Component 2: TranscriptionService (new)

**File:** `app/Services/TranscriptionService.php`

**Responsibilities:**
- Accept a file path (absolute path to temp file on disk)
- Open the file as a stream resource
- Call `$this->client->audio()->transcribe()` with model `whisper-1`
- Return the transcript text as a plain string
- Throw on failure (let caller handle)

**What it does NOT do:**
- Call the parser — single responsibility, caller chains the calls
- Store files — caller provides path, caller discards
- Know about Inspection models

**Communicates with:**
- OpenAI Client (injected via constructor, same singleton as `InspectionParserService`)

**Why a separate service (not added to InspectionParserService):** Transcription and field parsing are distinct operations. They have different inputs, different OpenAI endpoints, different failure modes, and different callsites (audio upload uses transcription only; typing uses parsing only; audio upload chains both). A `TranscriptionService` is independently testable and follows the existing naming convention.

---

### Component 3: InspectionParserService (existing — unchanged)

**File:** `app/Services/InspectionParserService.php`

**Responsibilities:** Parse a raw text string into structured inspection fields. No changes required.

The `parse(Inspection $inspection)` method is currently unused. It should remain for the `ParseInspectionNotes` job if async parsing is ever needed, but the primary integration path calls `parseRaw()` directly from the component.

---

### Component 4: Hive History Volt Component (new)

**File:** `resources/views/livewire/pages/hives/show.blade.php`

**Responsibilities:**
- Load a hive's inspections ordered by `inspected_at`
- Compute trend arrays server-side in the `with()` method
- Inject data into Alpine.js via `@js()` (Blade helper that JSON-encodes PHP values)
- Render a container `<canvas>` element for Chart.js to mount on
- Handle the Alpine.js `x-init` directive that initializes the chart

**What it does NOT do:**
- Use any server-side chart rendering library — Chart.js renders in the browser
- Fetch chart data via a separate AJAX/API call — data is embedded at page render time
- Manage chart state in Livewire — once chart initializes, Chart.js owns the canvas

**Communicates with:**
- `Inspection` model — reads via `$hive->inspections()->orderBy('inspected_at')->get()`
- Alpine.js in the browser (one-way: PHP writes data, Alpine reads it on init)

---

### Component 5: ParseInspectionNotes Job (existing — remains dormant)

The queue job exists and correctly calls `$parser->parse($inspection)`. It should not be activated for this project. Synchronous parsing is the right call: this is a personal single-user tool, the keeper is actively waiting for parsed results before reviewing them, and the job's async model (save first, fill fields later) conflicts with the review-before-save flow.

The job stays in the codebase as a valid extension point but is not dispatched.

---

## Data Flow

### Flow 1: Typed Notes → Parsed Fields (existing, to be wired)

```
User types in raw_notes textarea
  → Livewire debounced updatedRawNotes() fires (5000ms)
  → InspectionParserService::parseRaw($rawNotes)
    → OpenAI chat().create() with gpt-4o-mini, JSON mode
    → Returns JSON: field values + followup_questions array
  → Component maps array to Livewire properties
  → Livewire re-renders form with populated fields
  → followup_questions displayed as alert
User reviews fields, edits as needed, submits
  → validate() runs server-side
  → Inspection::create() persists record
```

**Direction:** User input → PHP → OpenAI → PHP → Livewire re-render → User reviews

---

### Flow 2: Audio Upload → Transcription → Parsed Fields (new)

```
User selects audio file (browser file input)
  → Livewire WithFileUploads handles chunked upload to temp storage
  → $audioFile property becomes TemporaryUploadedFile
  → Component triggers parseAudio() action (button or auto on upload)
    → Validate file: mime audio/*, max 25MB (Whisper API limit)
    → $audioFile->getRealPath() → absolute path string
    → TranscriptionService::transcribe($path)
      → fopen($path, 'r') → resource
      → OpenAI audio()->transcribe(['model' => 'whisper-1', 'file' => $resource])
      → Returns transcript text string
    → $this->rawNotes = $transcript (populates textarea)
    → InspectionParserService::parseRaw($transcript)
      → OpenAI chat().create() (same path as Flow 1)
    → Component maps result to properties
  → Livewire re-renders form
  → Temp file discarded (not saved to disk permanently)
```

**Direction:** Browser file → PHP temp → OpenAI Whisper → transcript string → OpenAI chat → PHP → Livewire re-render

**Key detail:** The audio file is never permanently stored. `TemporaryUploadedFile` lives in `storage/app/livewire-tmp/` for the duration of the request. After transcription, the raw transcript is stored in `raw_notes` when the inspection is saved — the audio itself is ephemeral.

**Whisper file constraints (HIGH confidence — stable API):**
- Accepted formats: `mp3`, `mp4`, `mpeg`, `mpga`, `m4a`, `wav`, `webm`
- Max file size: 25 MB
- `openai-php/client` passes the file via `fopen()` stream resource

---

### Flow 3: Hive History → Chart Render (new)

```
User navigates to /hives/{hive} (new route)
  → Volt component mounts
  → with() method queries inspections ordered by inspected_at
  → Builds PHP arrays: labels (dates), datasets (health score, varroa, frames)
  → Blade renders page with @js($chartData) injected into Alpine component
  → On page load, Alpine x-init fires
    → new Chart(canvas, config) with embedded data
    → Chart.js renders to <canvas>
No subsequent server calls for chart data
```

**Direction:** Route → PHP query → JSON in HTML → Alpine.js → Chart.js render (client-side)

**Why this approach over alternatives:**
- **Server-side chart library (e.g., LaravelCharts):** Generates SVG/PNG server-side. Loses Chart.js interactivity (tooltips, hover). Not worth it for this stack.
- **Separate AJAX endpoint for data:** Adds a route, a controller, and a network round-trip. Unnecessary when data is available at render time.
- **Livewire-driven chart updates:** Livewire re-renders morph the DOM and destroy Chart.js canvas instances unless carefully managed. For a static history view, Livewire should deliver data once at mount and leave the chart alone.
- **Recommended pattern:** `@js($chartData)` injects PHP data as JSON. Alpine `x-data` component initializes Chart.js on `x-init`. This is the standard Livewire + Alpine + Chart.js integration pattern.

---

## Synchronous vs Async AI Parsing

**Recommendation: Synchronous, for this project.**

The review-before-save flow requires the keeper to see parsed fields before submitting. This means parsing must complete before the form can be submitted. Async (queue) parsing would mean: save inspection first (with empty fields), then fill fields later — which removes the review step entirely.

The existing `ParseInspectionNotes` job implements exactly this async pattern. It calls `$parser->parse($inspection)` which persists field values directly. There is no way to use the queue job to support review-before-save.

Synchronous OpenAI calls in a personal single-user tool are appropriate:
- No concurrency concerns (single user)
- gpt-4o-mini response times are 1–4 seconds for this prompt size
- Whisper transcription for a short inspection audio (1–3 minutes) is 5–15 seconds
- Total latency for audio path (transcription + parsing) is 10–20 seconds — acceptable for this use case given user is waiting to review output

If latency becomes unacceptable, the component can show a loading spinner during the synchronous call using Livewire's `wire:loading` directive, which is already available without any queue infrastructure.

---

## Audio Upload: Livewire WithFileUploads

**Confidence: HIGH** — `WithFileUploads` is a stable Livewire v3 core trait, unchanged since v3.0.

**Integration pattern:**

```php
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $audioFile = null;

    public function parseAudio(): void
    {
        $this->validate([
            'audioFile' => ['required', 'file', 'mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm', 'max:25600'],
        ]);

        $path = $this->audioFile->getRealPath();
        // ... call TranscriptionService
    }
}
```

**How it works:**
- Livewire JS intercepts the `<input type="file">` change event
- File is uploaded in chunks to a temporary endpoint (`/livewire/upload-file`)
- Laravel stores it in `storage/app/livewire-tmp/`
- The component property becomes a `TemporaryUploadedFile` instance
- `getRealPath()` returns the absolute path to the temp file on disk
- Temp files are auto-cleaned after the request completes (or on next request via GC)

**File size note:** Livewire's default max upload size follows `php.ini` `upload_max_filesize`. For 25 MB Whisper limit, ensure `upload_max_filesize = 30M` and `post_max_size = 32M` in the dev environment.

---

## Chart.js + Alpine.js Integration Pattern

**Confidence: HIGH** — This is the canonical Livewire-compatible chart pattern.

**Why Chart.js over alternatives:**
- Already aligned with Tailwind/Alpine.js stack; no server-side dependencies
- DaisyUI provides no chart components — JS library is required
- Livewire docs recommend Chart.js + Alpine.js for charts in Livewire apps
- ApexCharts is a viable alternative but adds 400KB; Chart.js is lighter

**Integration pattern (Volt component):**

```blade
<?php
// PHP side: prepare chart data in with()
public function with(): array
{
    $inspections = $this->hive->inspections()->orderBy('inspected_at')->get();
    return [
        'chartData' => [
            'labels' => $inspections->pluck('inspected_at')->map->format('M j'),
            'healthScores' => $inspections->pluck('overall_health_score'),
            'varroaCounts' => $inspections->pluck('varroa_count'),
        ],
    ];
}
?>

{{-- Blade side: Alpine component initializes Chart.js once --}}
<div
    x-data="{ chart: null }"
    x-init="
        chart = new Chart($refs.canvas, {
            type: 'line',
            data: {
                labels: @js($chartData['labels']),
                datasets: [{
                    label: 'Overall Health',
                    data: @js($chartData['healthScores']),
                }]
            }
        })
    "
>
    <canvas x-ref="canvas"></canvas>
</div>
```

**Key detail:** `@js()` is a Blade helper (Laravel built-in) that JSON-encodes PHP values safely for JavaScript embedding. It handles null values, special characters, and type coercion correctly.

**Critical constraint — Livewire DOM morphing:** Livewire re-renders update the DOM via morphing. If a Livewire re-render touches the chart container, Chart.js loses its canvas reference. Solution: the chart container should be in a section that Livewire does not re-render after mount, OR use Alpine's `x-ignore` directive to tell Livewire to leave that subtree alone during morphing.

---

## Build Order (Phase Dependencies)

The four features have clear dependencies that determine build order:

### 1. Wire AI parsing end-to-end (no new components needed)
**Depends on:** Nothing new — `InspectionParserService` and the component both exist.
**What to build:** Connect `updatedRawNotes()` to show a loading state, display `followupQuestions` inline on the form, and handle the review-before-save flow.
**Why first:** Everything else depends on the inspect-and-review pattern being correct. Audio transcription feeds into this same flow. Charts depend on having real inspection data.

### 2. Audio upload + TranscriptionService
**Depends on:** AI parsing end-to-end (Flow 2 feeds directly into the parsing path from step 1)
**What to build:** `TranscriptionService`, add `WithFileUploads` to the create/edit component, add audio file input, wire the transcription → parse chain.
**Why second:** The transcription output becomes `rawNotes` — it re-enters the existing parse flow. If parsing isn't wired, there's nothing to feed the transcript into.

### 3. Hive history page + charts
**Depends on:** Real inspection data (requires the parse-and-save flow to work so there's meaningful data to chart)
**What to build:** New `hives/show.blade.php` Volt component, route, Chart.js integration via Alpine.
**Why third:** Charts are read-only views over saved data. They don't affect any other component. Building them last means the chart data will be real, not fabricated.

### 4. Schema migration for audio path (optional, deferred)
The current `inspections` table has no `audio_path` column. Since audio is ephemeral (transcribed, not stored), no migration is needed. If permanent audio storage is later desired, it would be a single addColumn migration.

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Storing Audio Files Permanently
**What goes wrong:** Adds file storage complexity, disk space concerns, and serves no user value — the transcript is what matters.
**Instead:** Transcribe immediately, populate `raw_notes` with the transcript, discard the temp file.

### Anti-Pattern 2: Queued AI Parsing in Review Flow
**What goes wrong:** Queue fires after save, fills in fields the user never reviewed. Destroys the core product value (keeper reviews AI output before it becomes the record).
**Instead:** Synchronous parsing before save, always. The `ParseInspectionNotes` job stays dormant.

### Anti-Pattern 3: Livewire-Managed Chart State
**What goes wrong:** If chart data is in a Livewire property and any interaction triggers a re-render, Livewire's DOM morphing destroys the Chart.js canvas context. Charts flash or disappear.
**Instead:** Inject chart data at page load via `@js()`. Use `x-ignore` on the chart container to prevent Livewire from morphing it. Chart.js owns the canvas entirely after init.

### Anti-Pattern 4: Passing TemporaryUploadedFile to the Service
**What goes wrong:** Services should not depend on Livewire internals (`TemporaryUploadedFile`). Creates a coupling between the service layer and the presentation framework.
**Instead:** Component calls `$this->audioFile->getRealPath()` and passes the string path to `TranscriptionService`. The service only needs a file path.

### Anti-Pattern 5: Adding Transcription Logic to InspectionParserService
**What goes wrong:** InspectionParserService has a single responsibility (parse text → fields). Adding a different OpenAI endpoint, different input type, and different output type bloats the service and makes each path harder to test.
**Instead:** Separate `TranscriptionService` with a single `transcribe(string $path): string` method. Component chains the two calls.

---

## Scalability Considerations

This is a personal single-user tool. Scalability is not a concern. These notes are relevant only if it ever becomes a shared/public tool:

| Concern | At 1 user (current) | At 100+ users |
|---------|---------------------|---------------|
| AI parsing latency | Synchronous fine | Would need queue + polling or SSE |
| Audio transcription | Synchronous fine | Queue + job status tracking |
| Chart queries | Direct query fine | Index on `hive_id, inspected_at` |
| SQLite concurrency | No issue | Switch to MySQL/Postgres |

---

## Sources

- Codebase analysis: `app/Services/InspectionParserService.php`, `app/Jobs/ParseInspectionNotes.php`, `resources/views/livewire/pages/inspections/create.blade.php`, `database/migrations/2026_04_02_102712_create_inspections_table.php`
- Livewire v3 `WithFileUploads` trait: stable core API since v3.0, `livewire/livewire` ^3.6.4 in composer.json (HIGH confidence)
- openai-php/client audio API: mirrors official OpenAI Audio API (`audio()->transcribe()`), `openai-php/client` ^0.19.1 in composer.json (HIGH confidence)
- Chart.js + Alpine.js + Livewire integration: canonical pattern documented in Livewire community, verified against known Livewire DOM morphing behaviour (HIGH confidence)
- `@js()` Blade helper: Laravel built-in, stable across Laravel 9–13 (HIGH confidence)
