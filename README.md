# WordPress Plugin Boilerplate

A modern WordPress plugin starter with PHP structure, frontend/admin assets, linting, and build tooling preconfigured.

## Install

### Option 1: Install directly from GitHub (recommended)

Run this in your WordPress plugins directory (for example `wp-content/plugins`):

```bash
curl -fsSL https://raw.githubusercontent.com/vijayhardaha/wordpress-plugin-boilerplate/refs/heads/master/install.sh -o install.sh && chmod +x install.sh && ./install.sh && rm -f install.sh
```

The installer will:

1. Ask for your plugin name.
2. Validate the name.
3. Generate slug/class/function/constant formats.
4. Download boilerplate source from GitHub.
5. Create a new plugin directory using the generated slug.
6. Replace boilerplate placeholders.
7. Remove setup-only files (`.git`, `README.md`, `bun.lock`, `bun.lockb`).

### Option 2: Clone and run locally

```bash
git clone https://github.com/vijayhardaha/wordpress-plugin-boilerplate.git
cd wordpress-plugin-boilerplate
bash install.sh
```

## After Install

Move into your generated plugin directory and install dependencies:

```bash
bun install
composer install
```

## Prerequisites

- `bash`
- `tar`
- `find`
- `perl`
- `curl` or `wget`
- `node` (optional, for JS tooling)
- `bun` and `composer` (for dependency install)

## Development

- Watch files: `bun run dev`
- Build assets: `bun run build`

## Linting

- Lint all: `bun run lint`
- Fix all: `bun run lint:fix`
- JS only: `bun run eslint`
- CSS/SCSS only: `bun run stylelint`
- PHP only: `bun run phplint`

## Translation

Generate POT file:

```bash
bun run makepot
```

Requires `wp-cli` (`wp i18n make-pot`).

## Build Release Zip

```bash
bun run build:zip
```

## Placeholder Conventions

The installer replaces these boilerplate placeholders:

| Placeholder     | Purpose                                                 |
| --------------- | ------------------------------------------------------- |
| `custom-plugin` | Text domain, slug, asset handles, file/directory naming |
| `custom_plugin` | Function name prefix                                    |
| `Custom Plugin` | Human-readable plugin name                              |
| `Custom_Plugin` | Class name prefix                                       |
| `CUSTOM_PLUGIN` | Constant name prefix                                    |

## Project Structure

- `custom-plugin.php`: Plugin bootstrap and singleton boot.
- `includes/`: Core, admin, and frontend PHP classes/functions.
- `src/`: Editable source assets (`scss`, `js`, images/fonts placeholders).
- `assets/`: Built/minified assets generated from `src/`.
- `gulpfile.mjs`: Build/watch pipeline.
- `bin/`: Packaging and POT helper scripts.

## License

GPL-2.0-or-later. See `LICENSE`.

## Contributions

Contributions and improvements are welcome.
