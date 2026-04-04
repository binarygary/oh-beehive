# Technology Stack — Additions Research

**Project:** Oh Beehive
**Researched:** 2026-04-04
**Scope:** Three feature additions to existing Laravel 13 + Livewire v3 + Volt app

---

## Context: What Already Exists

The existing stack is locked in (no framework changes):

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel 13 | ^13.0 |
| PHP | PHP | ^8.3 |
| Components | Livewire + Volt | ^3.6.4 / ^1.7.0 |
| Styling | Tailwind CSS | ^4.2.2 |
| UI Components | DaisyUI | ^5.5.19 |
| AI Client | openai-php/client | ^0.19.1 |
| Database | SQLite | dev/personal |

This research covers only the **additions** needed for three features:
1. AI parsing wired end-to-end
2. Audio upload → Whisper transcription → parse to structured fields
3. Inspection history trends (charts)

---

## Feature 1: AI Parsing End-to-End

### What Exists

- `InspectionParserService` calls `$this->client->chat()->create(...)` with `gpt-4o-mini`
- `ParseInspectionNotes` queue job exists but is never dispatched
- `openai-php/client ^0.19.1` is already registered as a singleton

### Recommendation: Synchronous, No New Packages

**No new packages required.** The service is complete. The wiring is missing.

Call `InspectionParserService::parseRaw(string $rawNotes)` directly in the Volt component's save action, populate form properties from the returned array, and render follow-up questions inline before the keeper confirms the save.

**Why synchronous, not queued:** The PROJECT.md key decisions table explicitly records this choice — "Simpler for single-user personal tool." The queue job exists as dead code from an earlier design. Synchronous is correct: the keeper is waiting for the AI response to review before saving. A queued approach would require polling or websockets, adding complexity with no benefit for a single-user tool.

**Model choice:** `gpt-4o-mini` is already hardcoded in `InspectionParserService`. This is correct — the system prompt and `json_object` response format are already proven. No model change needed.

**Confidence: HIGH** — verified by reading the actual service implementation and existing job.

---

## Feature 2: Audio Upload → Whisper Transcription → Parse

This requires three sub-components: file upload handling, Whisper API call, and a transcription service.

### 2a. Livewire File Upload

**Recommendation: Built-in Livewire `WithFileUploads` trait. No new packages.**

Livewire v3 ships `WithFileUploads` and `TemporaryUploadedFile` out of the box. The trait handles chunked XHR upload to a signed URL, temporary storage in `livewire-tmp/` on the local disk, cleanup of old uploads, and validation rules.

**How to use it in a Volt component:**

```php
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public ?TemporaryUploadedFile $audioFile = null;
```

In the Blade template:
```html
<input type="file" wire:model="audioFile" accept="audio/*,.m4a,.mp3,.wav,.webm,.ogg">
```

**Validation rule for audio:** Livewire's default max is 12 MB (from `FileUploadConfiguration::rules()`). For Whisper, the OpenAI limit is 25 MB. Override in the component's `rules()` method:

```php
public function rules(): array
{
    return [
        'audioFile' => ['required', 'file', 'mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm,ogg', 'max:25600'],
    ];
}
```

**Accepted MIME types for Whisper (OpenAI):** flac, mp3, mp4, mpeg, mpga, m4a, ogg, wav, webm. These map to Laravel's `mimes:` validation rule values.

**Temporary file path:** After upload, `$this->audioFile->getRealPath()` returns the absolute path on local disk. This path is passed directly to the transcription service. Do not permanently store audio — delete the temp file after transcription.

**Confidence: HIGH** — verified by reading `WithFileUploads.php` and `FileUploadConfiguration.php` in the vendored Livewire source.

### 2b. OpenAI Whisper Transcription

**Recommendation: `$client->audio()->transcribe([...])`. No new packages.**

`openai-php/client ^0.19.1` already includes a fully implemented `Audio` resource with a `transcribe()` method. Verified by reading `vendor/openai-php/client/src/Resources/Audio.php` and `vendor/openai-php/client/src/Responses/Audio/TranscriptionResponse.php`.

**API shape:**

```php
$response = $this->client->audio()->transcribe([
    'model' => 'whisper-1',
    'file'  => fopen($audioPath, 'rb'),
    'response_format' => 'text',
]);

$transcript = $response->text; // string
```

- `model`: Use `whisper-1` — it is the only production Whisper model available via the API as of 2025. OpenAI has not released a `whisper-2` via the API endpoint.
- `response_format`: Use `text` for plain transcript. `json` also works but adds parsing overhead with no benefit here since the transcript feeds straight into `InspectionParserService::parseRaw()`.
- `language`: Optionally pass `'language' => 'en'` to skip language detection and save a small amount of latency/cost.

**New service to create:** `App\Services\AudioTranscriptionService` that wraps the `audio()->transcribe()` call, accepts an absolute file path, returns the transcript string, and deletes the temp file after reading. Keep this separate from `InspectionParserService` — single responsibility.

**No new Composer packages needed.** The client is already installed.

**Confidence: HIGH** — verified by reading Audio.php and TranscriptionResponse.php in the installed vendor package.

### 2c. Wiring Audio Through the Inspection Form

**Flow:**
1. Keeper selects audio file — Livewire uploads to `livewire-tmp/`
2. Keeper clicks "Transcribe" — Volt component calls `AudioTranscriptionService::transcribe(path)`
3. Transcription fills `$this->rawNotes` on the component
4. Keeper reviews transcript, clicks "Parse Notes"
5. Existing AI parse flow runs exactly as in Feature 1

This keeps audio transcription as a pre-step that populates the same text field. No new database columns needed — the raw text ends up in `raw_notes` regardless of how it was entered.

**PHP `php.ini` consideration:** `upload_max_filesize` and `post_max_size` must be >= 25M for audio uploads. For local dev this is a config note, not a package dependency. The Livewire docs recommend setting these in `.env` by passing through the artisan serve command or your local php.ini.

**Confidence: HIGH** for the approach. MEDIUM for the exact PHP ini values — verify against the deployment environment.

---

## Feature 3: Inspection History Trends (Charts)

### The Decision: Chart.js via Alpine.js Directive

**Recommendation: Chart.js 4.x, wired via a minimal Alpine.js custom directive or `x-init`. No Laravel-specific charting package.**

**Why Chart.js:**
- Actively maintained (v4.4.x current as of 2025), MIT license
- Excellent line chart support — exactly what trends over time need
- Tiny Alpine.js integration pattern is idiomatic for this stack
- No server-side rendering required — data passes from Livewire as a JSON prop

**Why NOT a Laravel-specific charting package:**

| Package | Problem |
|---------|---------|
| `Chartisan` / `ConsoleTVs/Charts` | Effectively abandoned, last meaningful update 2021 |
| `Maatwebsite/Laravel-Charts` | Opinionated server-side API that fights Livewire's reactivity model |
| `Laravel Livewire Charts` | Unmaintained (last commit 2022), Livewire v2 era |
| Recharts / Victory | React libraries — wrong ecosystem |

**Why NOT ApexCharts:**
ApexCharts is popular with Alpine via `apexcharts` + `alpinejs-apexcharts` but is 500 KB+ bundle weight for what is effectively a simple line chart in a personal tool. Chart.js delivers the same output at ~200 KB.

**Installation:**

```bash
npm install chart.js
```

Version to pin: `^4.4.0` — Chart.js 4.x is the current stable major. Chart.js 3.x is EOL. Chart.js 5 is not yet released as stable.

**No Alpine plugin required.** Wire Chart.js directly in a Blade component using `x-init`:

```html
<canvas x-data x-init="
    new Chart($el, {
        type: 'line',
        data: { labels: $wire.labels, datasets: [{ data: $wire.scores }] },
        options: { responsive: true }
    })
"></canvas>
```

For Livewire reactivity (re-rendering when date filters change), listen for Livewire's `updated` lifecycle and destroy/recreate the chart, or use a dedicated Alpine component with `$watch`.

**Data shape from the backend:** The Volt component for the history page queries inspections ordered by `inspected_at`, serializes labels (dates) and datasets (scores, varroa_count, frame counts) as public properties. Livewire passes these to Alpine as `$wire.propertyName`. No API endpoint needed.

**Confidence: MEDIUM** — Chart.js 4.x is well-established; the Alpine integration pattern is standard. The specific `alpinejs-apexcharts` alternative was not verified against Tailwind v4 / DaisyUI v5 compatibility, hence the MEDIUM rather than HIGH on the chart library choice. Chart.js has no CSS coupling, making it safe.

---

## Summary: New Dependencies

| Package | Where | What For | Install Command |
|---------|-------|---------|----------------|
| `chart.js ^4.4.0` | npm (devDep) | Trend charts | `npm install chart.js` |

**No new Composer packages.** Everything else uses what is already installed.

---

## What NOT to Use

| Option | Category | Reason to Avoid |
|--------|----------|----------------|
| `spatie/laravel-medialibrary` | Audio storage | Full media management system for a field that gets deleted after transcription — massive overkill |
| `openai-php/laravel` (the full package) | AI client | Already using `openai-php/client` directly; the full package just wraps it with a facade. No benefit. |
| `livewire/volt` additional setup | File upload | Not needed; `WithFileUploads` works in Volt components out of the box |
| Any Laravel-specific chart package | Charting | All are unmaintained or fight Livewire's reactive model |
| `whisper.net` or local Whisper | Transcription | Self-hosted Whisper adds infrastructure complexity with no benefit for a personal tool on the same OpenAI account |
| Queued audio processing | Architecture | Adds polling/websocket complexity; synchronous is fine for files under 25 MB |

---

## PHP Configuration Notes (Not Packages)

These are environment settings, not package dependencies, but they are blockers if not set:

| Setting | Required Value | Why |
|---------|---------------|-----|
| `upload_max_filesize` | `25M` minimum | Whisper's 25 MB file limit |
| `post_max_size` | `26M` minimum | Must exceed `upload_max_filesize` |
| `max_execution_time` | `120` minimum | Whisper transcription + GPT parse in sequence for long audio files |

For local dev with `php artisan serve`, set these in `php.ini` or pass via `PHP_CLI_SERVER_WORKERS`.

---

## Sources

- `vendor/livewire/livewire/src/Features/SupportFileUploads/WithFileUploads.php` — WithFileUploads trait (verified)
- `vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php` — default rules, disk config (verified)
- `vendor/openai-php/client/src/Resources/Audio.php` — transcribe() method signature (verified)
- `vendor/openai-php/client/src/Responses/Audio/TranscriptionResponse.php` — response shape with `.text` property (verified)
- `vendor/openai-php/client/src/Client.php` — audio() method available on Client (verified)
- `app/Services/InspectionParserService.php` — existing parse flow (verified)
- `app/Jobs/ParseInspectionNotes.php` — queue job pattern (verified)
- Chart.js GitHub — v4.4.x current stable (MEDIUM confidence — not verified via direct fetch due to tool restrictions)
- `composer.json` / `package.json` — current installed versions (verified)
