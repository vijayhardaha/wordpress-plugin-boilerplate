# AGENTS.md

## Purpose

This repository is a WordPress plugin boilerplate for building a plugin with:

- PHP runtime code (`custom-plugin.php`, `includes/`)
- Source assets (`src/`)
- Compiled distributable assets (`assets/`)
- Build, lint, and packaging automation (`gulpfile.mjs`, `bin/`, `package.json`, `composer.json`)

## Rules First

Before making changes, read and follow project rules in `.rules`:

- [.rules/common.md](.rules/common.md)
- [.rules/javascript.md](.rules/javascript.md)
- [.rules/wordpress.md](.rules/wordpress.md)

Note: these are symlinks to shared global rule files and are intended to be the primary policy source for this repo.

## High-Level Architecture

1. `custom-plugin.php` is the plugin bootstrap loaded by WordPress.
2. It defines plugin constants and loads `includes/class-custom-plugin.php`.
3. `Custom_Plugin::instance()` initializes the singleton and:
   - registers core hooks (`init`, shutdown error logging)
   - loads shared includes
   - conditionally loads admin/frontend modules by request context
4. Admin module: `includes/admin/class-custom-plugin-admin.php`
   - registers admin menu/submenu
   - enqueues admin CSS/JS only on plugin screens
5. Frontend module: `includes/class-custom-plugin-frontend.php`
   - enqueues frontend CSS/JS
6. Shared functions:
   - `includes/custom-plugin-core-functions.php`
   - `includes/custom-plugin-frontend-functions.php`

## Directory Guide

- `custom-plugin.php`: main plugin file and entrypoint.
- `includes/`: PHP classes and function files.
- `src/`: editable source assets (`scss`, `js`, images/fonts placeholders).
- `assets/`: generated/minified build output. Do not hand-edit.
- `bin/`: release/build helper scripts.
- `gulpfile.mjs`: asset pipeline (watch/build/minification).
- `@install.sh`: script to install and configure the boilerplate (replaces `setup.mjs`).

## Common Commands

- Install deps: `bun install && composer install`
- Dev watch: `bun run dev`
- Production build: `bun run build`
- Lint all: `bun run lint`
- Auto-fix lint issues: `bun run lint:fix`
- Generate POT file: `bun run makepot`
- Build zip: `bun run build:zip`

## Change Conventions

- Prefer edits in `src/`; regenerate outputs into `assets/` via build scripts.
- Keep WordPress hooks/actions/filters and i18n textdomain usage consistent (`custom-plugin`).
- Follow coding standards configured by:
  - `phpcs.xml`
  - `eslint.config.mjs`
  - `stylelint.config.ts`
  - `prettier.config.mjs`

## Agent Workflow Expectations

1. Read `.rules/*` first.
2. Identify whether change is runtime PHP, admin UI, frontend UI, or build tooling.
3. Make minimal targeted edits.
4. Run relevant lint/build checks for touched areas.
5. Summarize changed files and any follow-up required.
