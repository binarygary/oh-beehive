# Directory Structure

## Top-level layout

```
oh-beehive/
├── app/                    # Application code (strict_types enforced)
│   ├── Console/Commands/   # Artisan commands
│   ├── Enums/              # Domain enums with helpers
│   ├── Http/Controllers/   # Thin HTTP controllers (Auth only currently)
│   ├── Jobs/               # Queue jobs (background work)
│   ├── Livewire/           # Livewire components and forms
│   │   ├── Actions/        # Single-action Livewire classes
│   │   └── Forms/          # Livewire Form objects
│   ├── Models/             # Eloquent models
│   ├── Providers/          # Service providers
│   ├── Services/           # Business logic services
│   └── View/Components/    # Blade view components (layouts)
├── bootstrap/              # Laravel bootstrap
├── config/                 # Laravel config files
├── database/
│   ├── factories/          # Model factories for testing
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── resources/
│   ├── css/                # Tailwind CSS (app.css — no tailwind.config.js)
│   ├── js/                 # Vite entry point
│   └── views/
│       ├── components/     # Blade components (layouts, nav)
│       └── livewire/
│           └── pages/      # Volt single-file components
│               ├── auth/   # Login, password reset pages
│               ├── hives/  # Hive CRUD pages
│               └── inspections/ # Inspection CRUD pages
├── routes/
│   ├── auth.php            # Auth routes
│   └── web.php             # App routes
├── scripts/                # GrumPHP helper scripts
├── tests/
│   ├── Architecture/       # Pest arch tests
│   ├── Feature/            # Feature tests (Livewire/Volt)
│   └── Unit/               # Unit tests (minimal)
├── .planning/              # GSD planning artifacts
│   └── codebase/           # This codebase map
├── CLAUDE.md               # Project context for Claude
├── composer.json           # PHP dependencies + scripts
├── grumphp.yml             # Pre-commit hook config
├── phpstan.neon            # Larastan config (level 5, app/ only)
└── vite.config.js          # Vite bundler config
```

## Key file locations

| What | Where |
|------|-------|
| Domain models | `app/Models/Hive.php`, `app/Models/Inspection.php` |
| AI parsing service | `app/Services/InspectionParserService.php` |
| Queue job (unused) | `app/Jobs/ParseInspectionNotes.php` |
| Enums | `app/Enums/HiveStatus.php`, `QueenStatus.php`, `VarroaMethod.php` |
| Hive pages (Volt) | `resources/views/livewire/pages/hives/` |
| Inspection pages (Volt) | `resources/views/livewire/pages/inspections/` |
| Shared layout | `resources/views/components/layouts/app.blade.php` |
| CSS config | `resources/css/app.css` |
| Route definitions | `routes/web.php` |
| Feature tests | `tests/Feature/HiveTest.php`, `tests/Feature/InspectionTest.php` |
| Arch tests | `tests/Architecture/ArchTest.php` |

## Naming conventions

- **Volt components:** kebab-case directory, blade file matches route segment (`create.blade.php`, `edit.blade.php`, `index.blade.php`)
- **Livewire component dot notation:** `pages.hives.index`, `pages.inspections.create`
- **Models:** PascalCase, singular (`Hive`, `Inspection`, `User`)
- **Enums:** PascalCase, descriptive (`HiveStatus`, `QueenStatus`, `VarroaMethod`)
- **Services:** `*Service.php` suffix
- **Jobs:** imperative verb phrase (`ParseInspectionNotes`)
- **Migrations:** timestamped, snake_case description
- **Tests:** plain descriptions via Pest `test()` / `it()`, no class wrappers
