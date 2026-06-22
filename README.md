# IMedia Registration — Repository

This repository contains the **IMedia Registration** WordPress plugin + standalone PHP admin app, plus the local tooling (knowledge graph, agent skills, design tokens) used to develop and document it.

> **Looking for install, configuration, or usage instructions?**
> Read the plugin's README: [`wp-registration-plugin/README.md`](wp-registration-plugin/README.md).
> That document covers the architecture, install steps, route map, data model, HMAC protocol, and day-to-day usage guides for both the WP plugin and the standalone app.

This root README is about the **repository itself** — its layout, its dev tooling, and the conventions for working in it (especially when working with an AI agent).

---

## 1. Repository layout

```
registration-app/
├── AGENTS.md                     Behavioral guidelines for AI coding agents
├── DESIGN.md                     Design tokens ("Vibrant Professionalism")
├── opencode-chat.md              Local opencode chat history
├── skills-lock.json              Pinned versions of installed agent skills
│
├── .gitignore                    Tracks plugin source; ignores runtime data
├── .graphifyignore               Tells graphify which files to skip
│
├── .agents/skills/               Installed AI agent skills (see §3)
├── .opencode/                    opencode runtime config
├── graphify-out/                 Knowledge graph output (graph.json, GRAPH_REPORT.md)
│
└── wp-registration-plugin/       The deliverable — WordPress plugin + standalone app
    ├── imedia-registration.php   Main plugin file (WordPress reads this)
    ├── README.md                 Full product documentation
    ├── app/                      Standalone app — Controllers, Models, Services, Core, Middleware
    ├── includes/                 WordPress integration layer (CPT, admin menus, REST, AJAX)
    ├── resources/                Assets + views (wordpress/, admin/, layouts/, partials/)
    ├── config/                   config.example.php — copy to config.php
    ├── database/                 schema.sql, migrations, seeds/seed.sql
    ├── cron/                     Outbox worker
    ├── public/                   Standalone app web root (front controller + uploads)
    ├── storage/                  Runtime logs
    ├── tests/                    p5_*, p7_*, p8_* smoke tests
    └── vendor/                   Vendored PHPMailer 6.x source
```

For the full inner directory tree, see [`wp-registration-plugin/README.md` §3](wp-registration-plugin/README.md#3-repository-layout).

---

## 2. What ships

| Artifact | Path | Audience |
|---|---|---|
| WordPress plugin | `wp-registration-plugin/imedia-registration.php` | WordPress site administrators |
| Standalone admin app | `wp-registration-plugin/public/` | App operators / staff |
| Plugin source code | `wp-registration-plugin/app/`, `includes/`, `resources/` | Plugin developers |
| Design tokens | `DESIGN.md` | Designers, front-end developers |
| Knowledge graph | `graphify-out/` | AI agents, code explorers |
| Agent skills | `.agents/skills/` | AI coding agents |

The deliverable lives in `wp-registration-plugin/`. Everything else at the root is **local dev tooling** — it supports development but is not shipped to production.

---

## 3. Local dev tooling

### 3.1 `graphify-out/` — knowledge graph

`graphify-out/` is a pre-built knowledge graph of the codebase. It's persisted in git so every session and every agent has the same map of the code without re-scanning from scratch.

The graph is regenerated on demand. After modifying code, run:

```
graphify update .
```

…or invoke `/graphify` for the full pipeline (detect → extract → cluster → label → report). The graph is AST-only when run with `update`, so it doesn't cost API tokens.

For focused questions, prefer the scoped queries (much smaller output than reading `GRAPH_REPORT.md`):

```
graphify query "How does HMAC submission verification work?"
graphify path "SubmitController" "Database"
graphify explain "OutboxWorker"
```

If `graphify-out/wiki/index.md` exists, it's the broad navigation index — read that before falling back to raw source browsing.

See [`AGENTS.md` §graphify](AGENTS.md#graphify) for the full rules.

### 3.2 `.agents/skills/` — AI agent skills

`.agents/skills/` holds project-specific skills for AI coding agents. Each skill is a `SKILL.md` (with optional `references/`) that the agent loads when relevant.

Available skills:

| Skill | Use for |
|---|---|
| `php-pro` | Modern PHP 8.3+, strict typing, PSR-12, DI, PHPStan level 9 |
| `wordpress-pro` | WordPress plugin/theme development, Gutenberg, WooCommerce, REST API |
| `database-optimizer` | Index design, query rewrites, slow-query diagnosis |
| `mysql` | MySQL/InnoDB schema, indexing, transactions, replication |
| `frontend-design` | Visual design direction, typography |
| `tailwind-design-system` | Tailwind v4 design tokens, component libraries |
| `ui-ux-pro-max` | 50+ styles, 161 palettes, 99 UX guidelines |
| `systematic-debugging` | Bug investigation workflow before proposing fixes |
| `documentation-writer` | Diátaxis-framework technical documentation |
| `find-skills` | Discover and install additional skills |

Skills are pinned via `skills-lock.json` — when installing or upgrading, update the lock file to match.

### 3.3 `AGENTS.md` — agent behavioral guidelines

[`AGENTS.md`](AGENTS.md) is the rulebook for any AI agent (opencode, Claude Code, etc.) operating in this repository. Read it before doing any work. The four sections are:

1. **Think Before Coding** — surface assumptions, push back, ask when unclear.
2. **Simplicity First** — no speculative features, no single-use abstractions.
3. **Surgical Changes** — touch only what the user asked; don't refactor adjacent code.
4. **Goal-Driven Execution** — define success criteria, loop until verified.

These bias toward caution over speed. For trivial tasks, use judgment.

### 3.4 `DESIGN.md` — design tokens

[`DESIGN.md`](DESIGN.md) is the single source of truth for the admin app's visual design — color palette, typography, spacing, motion. The token names in this file match the CSS custom properties consumed by the views.

The theme is **"Vibrant Professionalism"** — magenta `#b90064` for primary CTAs, cyan `#00658d` for secondary actions, navy `#3e5f7f` for chrome, with `Plus Jakarta Sans` / `Inter` / `JetBrains Mono` typography.

Do not hard-code colors in PHP views. Read the value from the corresponding CSS custom property (`var(--color-primary)`, etc.) so light/dark mode and theme changes flow through automatically.

### 3.5 `.opencode/` and `opencode-chat.md`

- `.opencode/` — opencode runtime config (modes, providers, permissions). Per-machine state, gitignored.
- `opencode-chat.md` — local session log. Gitignored.

---

## 4. How to use this repo

### 4.1 For humans

1. **Read the plugin README** first: [`wp-registration-plugin/README.md`](wp-registration-plugin/README.md). It covers install, configure, and operate.
2. **Browse the source**. The inner directory tree in the plugin README is the fastest map.
3. **Check `DESIGN.md`** when adjusting the admin UI's visual style.
4. **Check `AGENTS.md`** if you're working with an AI agent and want to know what conventions to enforce.

### 4.2 For AI agents

1. **Read [`AGENTS.md`](AGENTS.md)** before doing anything else. Its rules apply to every task in this repo.
2. **For codebase questions, query the graph first.** If `graphify-out/graph.json` exists (it does), use `graphify query`, `graphify path`, or `graphify explain` for a scoped subgraph — much smaller than grep or `GRAPH_REPORT.md`.
3. **Use the right skill.** PHP questions → `php-pro`. WordPress questions → `wordpress-pro`. UI work → `frontend-design` + `tailwind-design-system` (or `ui-ux-pro-max` for planning). Bug investigation → `systematic-debugging`. Documentation → `documentation-writer`. Use `find-skills` if a needed skill isn't installed.
4. **Follow the surgical-change rule.** Don't "improve" adjacent code. Every changed line should trace to the user's request. After your changes create orphans, clean them up; don't touch pre-existing dead code.
5. **After modifying code, refresh the graph:** `graphify update .` (AST-only, no API cost).

### 4.3 For designers

1. The design tokens live in [`DESIGN.md`](DESIGN.md) — color, typography, motion, spacing.
2. The component class vocabulary is in [`wp-registration-plugin/README.md` §12](wp-registration-plugin/README.md#12-design-system).
3. To add a new component class, edit the CSS file (no build step). The class will work in both light and dark mode because the color tokens are defined in both `:root` and `.dark`.

### 4.4 For operators

- **Install / configure** the WP plugin and standalone app: [`wp-registration-plugin/README.md` §4](wp-registration-plugin/README.md#4-install--step-by-step).
- **Day-to-day operation** (forms, registrations, outbox, exports): [`wp-registration-plugin/README.md` §15–16](wp-registration-plugin/README.md#15-usage--wordpress-plugin).
- **The cron job for the outbox worker** is in [`wp-registration-plugin/README.md` §10.2](wp-registration-plugin/README.md#102-automatic--cpanel-cron-job).

---

## 5. Ignore patterns

This repo has two ignore files, each for a different tool:

| File | What it does |
|---|---|
| `.gitignore` | Tells git which paths to skip (runtime data, secrets, editor clutter) |
| `.graphifyignore` | Tells the graphify scanner which paths to skip (vendored code, cache, runtime data) |

Both use `.gitignore`-style pattern syntax. The graphify output (`graphify-out/`) is tracked in git but **not** indexed by graphify itself — the tool's cache lives in `graphify-out/cache/`, which is gitignored.

See each file for the full list.

---

## 6. Conventions

- **One product, one repo.** The WordPress plugin and the standalone PHP app live in `wp-registration-plugin/` and ship together. Don't split them.
- **No build step.** The CSS, JS, and PHP ship as-is. No bundler, no Tailwind compile, no `npm install`. Edit the file; refresh the browser.
- **Strict types everywhere.** Every PHP file in `app/` declares `declare(strict_types=1);`. Keep that contract.
- **PSR-4 under `app/`.** Class names match their path: `App\Controllers\RegistrationsController` lives at `app/Controllers/RegistrationsController.php`. The autoloader is in `app/Core/Bootstrap.php`.
- **WordPress conventions under `includes/`.** Class files are `class-imf-*.php`, instantiated in `imedia-registration.php`, hooked via `add_action` / `add_filter`.
- **Templates are PHP files with HTML.** No Twig, no Blade. The renderer is `App\Core\View`; layouts are in `resources/views/layouts/`.
- **Surgical commits.** When working with an agent, prefer one focused commit per task over one mega-commit covering many refactors.

---

## 7. License

GPL v2 or later — same as the plugin it ships with. See [`wp-registration-plugin/README.md` §17](wp-registration-plugin/README.md#17-license).
