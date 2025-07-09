# Laravel QA Setup

A Laravel package providing an Artisan command to setup QA tooling with Laravel Sail,
including PHP and JavaScript linting, formatting, static analysis, testing, and config files.

## Installation

Require the package via Composer:

```bash
composer require lucasabato/laravel-qa-setup --dev
```

## Usage

Run the setup command:

```bash
php artisan setup:qa
```

This will:

- Detect if `pnpm` or `npm` is used and install packages accordingly
- Detect if Inertia.js is installed and install Vue-related tools only if needed
- Install Composer dev dependencies (Pint, Larastan, PHPUnit, Laravel Insights)
- Install JavaScript dev dependencies (ESLint, Prettier, Vitest, etc.)
- Add helpful scripts to `composer.json` and `package.json`
- Generate missing config files (`.eslintrc.cjs`, `.prettierrc`, `tsconfig.json`, etc.)
- Run initial frontend build via Sail

## Requirements

- Laravel 10+
- PHP 8.1+
- Laravel Sail installed and running
- `jq` CLI installed locally

## License

MIT
