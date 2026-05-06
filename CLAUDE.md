# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend:** Laravel 13, PHP 8.5, served via Laravel Herd, MySQL
- **Frontend:** Blade + Livewire 3 + Tailwind CSS v4 (via Vite)
- **Single user app** — no multi-tenancy, no registration flow. Auth is a simple login for one user.
- Tests run against SQLite in-memory (`phpunit.xml`).

> Livewire 3 and MySQL are planned but not yet installed. Switch `.env` `DB_CONNECTION` to `mysql` once MySQL is configured.

---

## Business Logic

### Budgeting Periods
- A period runs from the **15th of a month** to the **14th of the next month** (e.g., "15 Apr 2026 – 14 May 2026")
- Periods are auto-generated — when the app loads, ensure the current period exists
- The current period is the one where `today` falls between `start_date` and `end_date`
- All overviews and data are scoped to a period; users can switch to previous periods

### Accounts (fixed, not user-configurable)
- **Rabobank** — primarily fixed costs
- **Revolut** — short-term and long-term spends
- **American Express** — credit card, paid in full each period

### Categories (defaults, user can add/rename/archive in Settings)
1. Fixed Costs
2. Long-term Spends
3. Short-term Spends
4. Savings
5. Investments

### Transactions
- Fields: date, description, amount (positive = income/top-up, negative = expense), account, category, period
- Can be imported via CSV (one parser per bank) or added manually
- Categorization is always manual
- Uncategorized transactions must be clearly flagged everywhere

### AmEx Payoff Logic
- At period end, group AmEx transactions by category:
  - Short-term Spends → pay from Revolut short-term pocket
  - Long-term Spends → pay from Revolut long-term pocket
  - Fixed Costs → pay from Rabobank
- Show a split summary with totals per category
- "Mark as paid" button with date (defaults to 16th of the month, editable)
- Available from the 1st of the month (user can pay early)

### Budget Targets
- Soft targets per category per period (no hard limits or alerts)
- Shown as a visual progress bar: spent vs target
- Configured in Settings

### Net Worth Accounts (user-configured in Settings)
- Types: savings, investment
- Each has a name, type, current balance, and last-updated timestamp
- Balance is manually updated; every update saves a snapshot (for growth history)
- Dashboard shows all balances + total net worth

---

## Database Models

### Period
`id, start_date, end_date, is_current (bool), amex_paid_at (nullable datetime), timestamps`

### Account
`id, name (rabobank|revolut|amex), label, color (hex), timestamps`

### Category
`id, name, type (transactional|savings|investment), color (hex), is_archived (bool), sort_order, timestamps`

### Transaction
`id, period_id (FK), account_id (FK), category_id (FK nullable), date, description, amount (decimal 10,2), source (import|manual), import_hash (duplicate detection), notes (nullable), timestamps`

### BudgetTarget
`id, category_id (FK), period_id (FK), amount (decimal 10,2), timestamps`

### NetWorthAccount
`id, name, type (savings|investment), notes (nullable), is_active (bool), sort_order, timestamps`

### NetWorthSnapshot
`id, net_worth_account_id (FK), balance (decimal 12,2), recorded_at (datetime), timestamps`

---

## UI Layout
- **Left sidebar:** fixed, dark background — app name "Veltiq Budget", current period label, nav: Dashboard, Transactions, History, Settings
- **Main content:** light background, full width
- **Color accent:** deep blue / slate
- All pages scoped to a selected period (period switcher in sidebar or top bar)

## Pages
1. **Dashboard** — period summary cards, category progress bars, AmEx payoff split, net worth snapshot
2. **Transactions** — full list grouped by date, inline categorization, filters
3. **History** — previous periods overview
4. **Settings** — categories, budget targets, net worth accounts

---

## Coding Conventions
- Use Livewire 3 components for all interactive UI
- Use Laravel Form Requests for validation
- Use database seeders for default categories and accounts
- Keep controllers thin — logic in service classes or Livewire components
- Use Tailwind utility classes only — no custom CSS unless absolutely necessary
- All amounts stored as `decimal(10,2)`, displayed with € symbol and Dutch number formatting (comma as decimal separator)

## Auth

Single-user session auth — no registration. Credentials live in `.env`:

- `APP_USER_EMAIL` — login email
- `APP_USER_PASSWORD` — bcrypt hash of the password (use `php artisan tinker --execute 'echo bcrypt("yourpassword");'` to generate)

Default dev credentials: `admin@veltiq.test` / `password`

Routes are protected by `RequireAuth` middleware (global web middleware, skips `/login`).

## Seeding

```bash
# Reset DB and run base seed (accounts + categories)
php artisan migrate:fresh --seed

# Add realistic dummy transactions for two past periods + current period
php artisan db:seed --class=DummyDataSeeder
```

## Commands

```bash
# Start full dev environment (server + queue + logs + Vite hot-reload)
composer run dev

# Run all tests
php artisan test --compact

# Run a single test file
php artisan test --compact tests/Feature/ExampleTest.php

# Run tests matching a name
php artisan test --compact --filter=testName

# Lint / fix PHP style (run after any PHP edits)
vendor/bin/pint --dirty --format agent

# Build frontend assets
npm run build
```

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
