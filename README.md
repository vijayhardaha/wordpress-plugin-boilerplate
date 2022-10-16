# WordPress Plugin Boilerplate

A Wordpress starter pack with pre-configured Gulp to set up a custom Wordpress Plugin Boilerplate in a minute.

## Getting started

Before running any of the scripts, you must complete several steps:

1. Clone the GitHub repository into your plugins directory.
1. In the plugin directory run `pnpm i && composer install`.

After cloning repository and installing the dependencies, you need to complete setup process using these steps:

-   Run `pnpm run setup` to start automated setup process.
-   Write the desired plugin name in prompt input. e.g. `WP Mail Checker` `Bulk Email Sender`
-   Automated script will setup plugin name and relative keywords in all the plugin files and you will see `Complete!` message after a successful process, otherwise you'll see error messages.

### Watch and Build

-   Run `pnpm run dev` to compile your files automatically whenever you've made changes to the associated files.
-   Run `pnpm run build` to compile files for release.

### Lint and Fix

The plugin has a total of 8 scripts for lint and fix. There are 3-3 scripts set for lint & fix the `css, js, php` individually and 2 combined scripts to lint & fix `css, js, php` together.

-   Run `lint:css` to lint `scss` files.
-   Run `lint:js` to lint `js` files.
-   Run `lint:php` to lint `php` files.
-   Run `lint` to lint `css, js, php` files together.
-   Run `lint-fix:css` to fix `scss` files.
-   Run `lint-fix:js` to fix `js` files.
-   Run `lint-fix:php` to fix `php` files.
-   Run `lint-fix` to fix `css, js, php` files together.

### Generate POT(languages) file

1. Install WP-CLI and add it to PATH (check out [official guide](https://wp-cli.org/#installing))
1. Run `pnpm run makepot`

### Important keywords

| Keyword         | Used as                                              |
| --------------- | ---------------------------------------------------- |
| `custom-plugin` | Files `prefix` or `postfix`                          |
| `custom-plugin` | Plugin `text-domain`, Assets enqueue handle `prefix` |
| `Custom Plugin` | Plugin `name` across all plugin files                |
| `Custom_Plugin` | Final class `name`, Class name `prefix`              |
| `custom_plugin` | Main function `name`, functions name `prefix`        |
| `CUSTOM_PLUGIN` | Constants `prefix`                                   |

## How setup works?

In Plugin Boilerplate files **Important Keywords** ☝️ are used as `placeholders` which are replaced by an automated setup process with your given plugin name.

`pnpm run setup` is a one-time command, After successful completion, `setup.mjs`, `.git`, `README.md` files/directories will be deleted and `setup` script will be removed from the `package.json`

The setup process’s dependencies will be removed as well and you’ll be left with dependencies that will be used in your plugin development.

## Plugin structure

### custom-plugin.php

The main plugin file contains all the plugin information and some defined `CONST` and a global function that returns the main class instance.

### gulpfile.mjs

It has all the tasks setup for `css, js, image, font` files.

### includes

This directory has all the php files.\
class files will start with `class-` prefix and functions files will end with `-functions.php` postfix.\
All the backend-related files will be inside the `admin` directory.

### src

This directory has all the source files for plugin assets.

### assets

This directory has all the compressed & optimized `css, js, image, font` files.\
All the build assets will be auto-generated with the help of **gulp** tasks in this directory, You don't have to & should not write/create anything inside it since it will be deleted and recreated with `build` script.

## License

Copyright (C) 2021-2022, Vijay Hardaha. WordPress Plugin Boilerplate is distributed under the terms of the GNU GPL.

## Contributions

Anyone is welcome to contribute.

---

Made with ❤ by [Vijay Hardaha](https://twitter.com/vijayhardaha)
