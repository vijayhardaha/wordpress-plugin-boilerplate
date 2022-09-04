# WordPress Plugin Boilerplate

A Wordpress starter pack with pre-configured Gulp to set up a custom Wordpress Plugin Boilerplate in a minute.

## Getting started

- Clone the GitHub repository into your plugins directory.
- `git clone https://github.com/vijayhardaha/wordpress-plugin-boilerplate.git wp-demo-plugin`
- Use your desired plugin name in place of **_wp-demo-plugin_** in above command.
- Navigate to the cloned directory using `cd` command.
- In the plugin directory run `npm i` and `composer install`.
- Run `npm run setup` to start automated setup process.
- You will be prompt to enter the plugin name. Use the desired unique plugin name in input.\
  These are some examples of plugin names: **WP Mail Checker**, **Bulk Email Sender**, **WP Users Importer**
- After a valid input, In a few seconds, you'll see **Complete!** message if everything goes ok.
- Run `npm run dev` to start development.
- Run `npm run build` to build the assets files & .pot file.

## Important keywords

| Keyword       | Description                                                  |
| ------------- | ------------------------------------------------------------ |
| custom-plugin | Used as prefix/postfix in class/Fn(s) filenames.             |
| Custom Plugin | Used as a plugin name across all plugin files.               |
| Custom_Plugin | Used in the final class name & as other class name prefixes. |
| CUSTOM_PLUGIN | Used in constants prefix.                                    |
| custom-plugin | Used as WordPress text domain and prefix for enqueue assets. |
| custom_plugin | Used as main Fn name and as other Fn(s) name prefix.         |

## How setup works?

In this plugin boilerplate code above **Important keywords** are used in a way that helps the setup code to match and replace the keywords with your given plugin name.

`npm run setup` is a one-time command, After successful completion, the setup code and script will be removed from the `package.json` and some of the packages that are used in the setup will be removed as well and you'll only have packages that are used in plugin development ahead.

## Plugin structure

**custom-plugin.php**\
Main plugin file which contains all the plugin information and some defined CONST and a global functions that returns main class instance.

**gulpfile.js**\
It has all the tasks setup for **_js, css, image, and font_** files.

**languages**\
This directory contains a .pot file that will be used for translation.

**includes**\
This directory contains all the php files.\
class-based files will start with `class-` prefix and functions files will end with `-functions.php` postfix.\
All the backend-related files will be inside the `admin` directory.

**src**\
This directory contains all the source files for plugin assets.\
**_js, css, image, and font_** files will be in the related directory.\
This setup uses [asset-builder](https://www.npmjs.com/package/asset-builder) package, Check their documentations to know more about it.

**assets**\
This directory contains all the compressed & optimized **_js, css, image, and font_** files.\
All the build assets will be auto-generated with the help of **gulp** tasks in this directory, You don't have to write/create anything inside it.

## License

WordPress Plugin Boilerplate, Copyright (C) 2021-2022, Vijay Hardaha.\
WordPress Plugin Boilerplate is distributed under the terms of the GNU GPL.

## Contributions

Anyone is welcome to contribute.

---

Made with ❤ by [Vijay Hardaha](https://twitter.com/vijayhardaha)
