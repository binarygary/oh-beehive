# Technology Stack

**Analysis Date:** 2026-04-04

## Languages

**Primary:**
- PHP 8.3+ - Backend application logic, models, services, controllers

**Secondary:**
- JavaScript (ES modules) - Frontend interactivity via Livewire components
- HTML (Blade templates) - View rendering via Laravel Blade + Livewire

## Runtime

**Environment:**
- PHP 8.3+ (CLI and built-in server via `php artisan serve`)
- Node.js (required for npm package management and Vite build process)

**Package Managers:**
- Composer 2.x - PHP dependency management
  - Lockfile: `composer.lock` (11,950 lines)
- npm - JavaScript dependency management
  - Lockfile: `package-lock.json` (implied from package.json)

## Frameworks

**Core:**
- Laravel 13.0+ - Full-stack web framework
- Livewire v3.6.4+ - Real-time reactive components without writing JavaScript
- Livewire Volt v1.7.0+ - Single-file Livewire component syntax

**Frontend/Styling:**
- Tailwind CSS v4.2.2 - Utility-first CSS framework via `@tailwindcss/vite`
  - Config: Inline in `resources/css/app.css` using `@theme` blocks (no `tailwind.config.js`)
- DaisyUI v5.5.19 - Tailwind component library loaded via `@plugin "daisyui"`
- `@tailwindcss/forms` v0.5.11 - Form input component styles
- Laravel Breeze v2.4 (downgraded scaffolding) - Initial auth UI shell

**Build/Dev:**
- Vite v8.0.0+ - Fast module bundler for dev server and production builds
  - Config: `vite.config.js`
  - Plugin: `laravel-vite-plugin` v3.0.0+ for Laravel integration
- concurrently v9.0.1 - Run multiple dev servers in parallel

**Testing:**
- Pest v4.4+ - PHP testing framework
- Pest Laravel Plugin v4.1+ - Laravel-specific testing utilities
- Pest Architecture Plugin v4.0+ - Architecture testing via Pest
- Mockery v1.6+ - Mocking library for tests
- FakerPHP v1.23+ - Test data generation

**Code Quality:**
- PHPStan (Larastan) v3.9+ - Static type analysis at level 5
  - Config: `phpstan.neon` (analyzes `app/` only)
- Laravel Pint v1.27+ - PHP code formatter and auto-fixer
- GrumPHP v2.19+ - Git pre-commit hook runner

**Development Tools:**
- Laravel Tinker v3.0+ - Interactive REPL for Laravel
- Laravel Pail v1.2.5+ - Real-time log streaming

## Key Dependencies

**Critical:**
- `openai-php/client` v0.19.1+ - OpenAI API client for inspection parsing
  - Used in: `app/Services/InspectionParserService.php`
  - Registered in: `app/Providers/AppServiceProvider.php` as singleton

**Infrastructure:**
- `laravel/framework` core packages handle:
  - Eloquent ORM for database models (`app/Models/`)
  - Blade template engine for views
  - Database migrations and schema building
  - Authentication via guard system
  - Session management (database-backed)
  - Queue system (database driver by default)
  - Cache system (database driver by default)
  - File storage via Flysystem abstraction

## Configuration

**Environment:**
- `.env` file (not committed; use `.env.example` as template)
- Key vars configured:
  - `APP_*` - Application name, environment, debug mode, URL
  - `DB_*` - Database connection (SQLite default)
  - `MAIL_*` - Mail service configuration
  - `QUEUE_CONNECTION` - Background job handler
  - `CACHE_STORE` - Cache backend
  - `SESSION_DRIVER` - Session storage (database)
  - `OPENAI_API_KEY` - OpenAI API authentication
  - `AWS_*` - AWS credentials for optional S3/SES
  - `REDIS_*` - Redis connection (optional, not required)

**Build:**
- `vite.config.js` - Vite bundler configuration
- No `tailwind.config.js` - Tailwind v4 config via `@theme` in `resources/css/app.css`
- `phpstan.neon` - PHPStan static analysis rules
- `composer.json` - PHP scripts:
  - `composer test` - Clear config + run Pest
  - `composer lint` - Run PHPStan
  - `composer format` - Run Pint auto-fix
  - `composer format:check` - Check Pint without fixing
  - `composer dev` - Run all dev servers (Laravel, queue, logs, Vite)
- `scripts/` directory contains helper scripts:
  - `pint-check.sh` - Pint validation for GrumPHP
  - `pest-run.sh` - Pest test runner for GrumPHP

## Platform Requirements

**Development:**
- PHP 8.3+ (CLI and built-in server)
- Node.js (for npm packages and Vite)
- Composer 2.x
- Git (GrumPHP hooks on commit)
- SQLite (default; file at `database/database.sqlite`)

**Production:**
- PHP 8.3+ with required extensions (PDO SQLite or MySQL, JSON, etc.)
- Web server (Apache, Nginx, or PHP built-in)
- Optional: Redis for session/cache optimization
- Optional: AWS S3 for file storage (configured but not required)
- Optional: Mail service (Postmark, Resend, SendMail, AWS SES)

**Node Tooling:**
- No version pinning file (`.nvmrc` not present)
- Uses npm from PATH

---

*Stack analysis: 2026-04-04*
