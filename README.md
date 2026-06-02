# Issue Intake & Smart Summary System

Laravel 11 API + React UI for support/ops issue intake, team comments, filterable listing, and **asynchronous** AI/rules-based summaries.

## Stack

| Piece | Choice |
|-------|--------|
| API | Laravel 11 (PHP 8.2+) |
| UI | React 18 + Vite (`resources/js`, served via Blade) |
| Database | **SQLite** — zero external services, single file, ideal for local assessment and CI |
| Queue | Database driver (`jobs` table) |
| Summary | OpenAI when `OPENAI_API_KEY` is set; otherwise **rules-based fallback** (same `SummaryGenerator` interface) |

### Why SQLite?

Portable, fast to reset, and ships with PHP. Foreign keys and indexes work well for this relational model (`issues` → `comments`). Production could switch to PostgreSQL by changing `DB_CONNECTION` only.

---

## Requirements

- PHP 8.2+ with extensions: `pdo_sqlite`, `mbstring`, `openssl`, `curl`, **`zip`** (required by Composer)
- [Composer](https://getcomposer.org/)
- Node.js 18+ and npm (for the React UI)

**XAMPP:** enable `extension=zip` in `C:\xampp\php\php.ini` (uncomment `;extension=zip`), then run `composer install` before any `artisan` command.

---

## Setup (clean machine)

```bash
# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env   # Windows: copy .env.example .env
php artisan key:generate

# 3. Database file + schema
touch database/database.sqlite   # Windows: type nul > database\database.sqlite
php artisan migrate

# 4. Sample data (5 issues, 5 comments)
php artisan db:seed

# 5. Terminal A — API
php artisan serve

# 6. Terminal B — queue worker (required for summaries)
php artisan queue:work --tries=3

# 7. Frontend assets
npm install
npm run build   # or `npm run dev` while developing (see below)
```

**Development (two terminals):**

```bash
# Terminal A
php artisan serve

# Terminal B — Vite HMR (keep running)
npm run dev
```

Open **http://127.0.0.1:8000** — Laravel serves the React SPA; API calls use the same origin (`/issues`, etc.).

> **Do not open port 5173 in the browser.** Vite on `:5173` only serves JS/CSS assets for Laravel. The UI lives at **http://127.0.0.1:8000**.  
> After `npm run build`, you only need `php artisan serve` (no Vite dev server required).

**Production:** `npm run build` compiles assets to `public/build/`.

**Make shortcuts:** `make setup` (includes `npm run build`), `make serve`, `make worker`, `make vite` for dev.

**Tests:** `php artisan test` (uses in-memory SQLite; queue faked where needed).

---

## Web UI

Lives in standard Laravel paths:

| Path | Purpose |
|------|---------|
| `resources/js/` | React components, pages, API client |
| `resources/css/app.css` | Styles |
| `resources/views/app.blade.php` | SPA shell (`@vite`) |
| `routes/web.php` | Serves Blade for `/`, `/create`, `/issues/{id}` (browser) |

| Screen | Route |
|--------|-------|
| Issue list | `/` |
| Create issue | `/create` |
| Issue detail | `/issues/{id}` |

`GET /issues/{id}` returns JSON when `Accept: application/json` (API clients, tests); otherwise returns the SPA shell.

---

## `needs_attention` flag

| When | Behavior |
|------|----------|
| **Create** | Set from priority: `true` if `priority = high` |
| **PATCH** | Recomputed **only** when `priority` is in the request body |
| **Other updates** | Unchanged (e.g. status-only PATCH does not touch the flag) |

Implemented in `IssueAttentionService` and called from `IssueController` / `Issue::recomputeNeedsAttention()`.

---

## API

Base URL: `http://127.0.0.1:8000` (no `/api` prefix).

JSON responses use a `data` wrapper (Laravel API resources). Errors: `{ "message": "...", "errors": { "field": ["..."] } }` with 422 for validation.

### Create issue (201, `summary_status: pending`)

```bash
curl -s -X POST http://127.0.0.1:8000/issues \
  -H "Content-Type: application/json" \
  -d "{\"title\":\"Checkout 500\",\"description\":\"Users see 500 on payment.\",\"priority\":\"high\",\"category\":\"incident\"}"
```

### List with combined filters

```bash
curl -s "http://127.0.0.1:8000/issues?status=open&priority=high&category=billing"
```

### View one issue (comments eager-loaded)

```bash
curl -s http://127.0.0.1:8000/issues/1
```

### Update issue

```bash
# Re-triggers summary job (description changed)
curl -s -X PATCH http://127.0.0.1:8000/issues/1 \
  -H "Content-Type: application/json" \
  -d "{\"description\":\"Updated details after investigation.\"}"

# Does NOT re-trigger summary
curl -s -X PATCH http://127.0.0.1:8000/issues/1 \
  -H "Content-Type: application/json" \
  -d "{\"status\":\"in_progress\"}"
```

### Add comment (201)

```bash
curl -s -X POST http://127.0.0.1:8000/issues/1/comments \
  -H "Content-Type: application/json" \
  -d "{\"author_name\":\"Alex\",\"body\":\"Checking logs now.\"}"
```

---

## Architecture & decisions

1. **Thin controllers** — validation in Form Requests; JSON via API Resources; business rules on the model/services.
2. **Async boundary** — `GenerateIssueSummaryJob` dispatched on create and on description update only. HTTP returns immediately with `summary_status = pending`.
3. **Summary seam** — `SummaryGenerator` interface. `LlmSummaryGenerator` calls OpenAI using `resources/prompts/issue_summary.txt`; on missing key or API failure it delegates to `RulesBasedSummaryGenerator`.
4. **N+1 avoidance** — list endpoint does not load comments; show uses `$issue->load('comments')` (two queries). Covered by a query-count test.
5. **React UI** — Laravel Vite integration (`laravel-vite-plugin`); same-origin API calls; `GET /issues/{id}` uses content negotiation for JSON vs SPA.
6. **No auth** — assessment scope; API is open. Would add Sanctum/token middleware at the route group for production.
7. **Failed jobs** — job `failed()` sets `summary_status = failed`; entries also land in `failed_jobs` when using `queue:work`.

```
POST /issues → IssueController → DB → dispatch GenerateIssueSummaryJob
                                              ↓
                                    queue:work → SummaryGenerator → update issue
```

---

## Worker

Summaries are **not** generated during the HTTP request. Start a worker:

```bash
php artisan queue:work --tries=3
```

Optional LLM: set `OPENAI_API_KEY` in `.env`. Without it, the rules engine runs automatically.

---

## Tests (8 cases)

```bash
php artisan test
```

Covers: create success, validation failure, combined filters, add comment, N+1 guard, job dispatch on create, job populates summary fields, high-priority `needs_attention`, description vs status update queue behavior.

---

## Commit guide

See **[COMMITS.md](COMMITS.md)** for step-by-step commit commands (8 commits + optional lockfile).

---

## AI usage note

- **Tooling:** Cursor / Claude used to scaffold Laravel structure, migrations, tests, and README from the assessment brief.
- **Used for:** boilerplate layout, rules-engine keyword maps, test matrix, React UI, documentation.
- **Reviewed manually:** async trigger rules (description-only), `needs_attention` recompute policy, N+1 eager load, LLM fallback seam, validation (trim + reject empty strings), API route prefix (`apiPrefix: ''`), and UI polling behavior.
- **Ownership:** All design trade-offs above are intentional; be ready to extend `SummaryGenerator` or add auth without restructuring.
