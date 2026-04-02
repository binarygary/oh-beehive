# Oh Beehive — Claude Context

## What this is
A Laravel app for recording beehive inspections with AI assistance. The keeper types free-text inspection notes; the AI parses them into structured fields and asks follow-up questions for anything it couldn't resolve.

**Future enhancement:** Live dictation with real-time field checkmarks as AI detects them.

## Stack
- Laravel 13, PHP 8.3
- Livewire v3 + Volt (Breeze scaffolded — Breeze downgraded Livewire from v4 to v3)
- Tailwind CSS v4 via `@tailwindcss/vite` (no `tailwind.config.js` — config lives in `app.css`)
- DaisyUI v5 + `@tailwindcss/forms` loaded via `@plugin` in `resources/css/app.css`
- SQLite (dev + in-memory for tests)

## Key decisions
- **No registration route** — users created only via `php artisan make:user`
- **Multi-user** — every hive and inspection has a `user_id` FK
- **All Langstroth** — no hive type field
- **Treatment is free text** — not structured
- **AI flow (round 1):** textarea → OpenAI parses `raw_notes` → fills structured fields → follow-up questions for unresolved fields stored in `followup_questions` JSON column

## Composer scripts
```bash
composer test          # clear config + run pest
composer lint          # larastan
composer format        # pint (auto-fix)
composer format:check  # pint (check only)
composer dev           # all dev servers (Laravel + queue + pail + Vite)
```

## Toolchain
GrumPHP runs automatically on every `git commit`:
1. Larastan (PHPStan level 5, `phpstan.neon`, `app/` only)
2. `scripts/pint-check.sh` — Pint in check mode
3. `scripts/pest-run.sh` — full Pest suite

Test suites: `Unit`, `Feature`, `Architecture` (defined in `phpunit.xml`).

## Rules every new file must follow
- **`declare(strict_types=1);`** after `<?php` in every `app/` file — enforced by arch test
- Livewire components extend `Livewire\Component`
- Livewire form objects extend `Livewire\Form`
- Controllers extend `App\Http\Controllers\Controller`, no direct Eloquent use
- Models extend `Illuminate\Database\Eloquent\Model`, only used within `App\` and `Database\`
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()` in `App\`

## Domain models

### Hive
`user_id`, `name`, `location` (nullable), `acquired_at` (nullable date), `status` (HiveStatus enum: active/inactive/dead_out), `notes` (nullable text)

### Inspection
Belongs to `Hive` + `User`. Key fields:
- `raw_notes` — the keeper's typed observations (AI input)
- `followup_questions` — JSON array of questions AI couldn't resolve
- Numeric scores 1–5: `brood_pattern_score`, `honey_stores_score`, `temperament_score`, `overall_health_score`
- Booleans: `queen_seen`, `eggs_present`, `larvae_present`, `capped_brood_present`, `feeding_done`
- Counts: `frames_of_brood`, `frames_of_bees`, `frames_of_honey`
- `queen_status` enum: laying / not_laying / swarm_cells / supersedure_cells
- `varroa_count` (per 100 bees), `varroa_method` enum — optional, used a few times/year
- `disease_observations` (JSON array of strings)
- `treatment_applied` (free text)

## What's next
1. Basic UI shell — DaisyUI nav/layout, dashboard
2. Hive CRUD — list, create, edit, delete
3. Inspection form — raw_notes textarea + AI parsing + follow-up questions
4. AI integration — OpenAI API parses `raw_notes` into structured inspection fields
