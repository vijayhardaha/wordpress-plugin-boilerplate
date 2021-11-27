# WordPress Plugin Boilerplate
A Wordpress starter pack with pre-configured Gulp to setup custom Wordpress Plugin Boilerplate in a minute.

## Setup Your Plugin

Download this repositary and extract and rename the folder to your desired plugin name or clone this repositary using this following command:

<pre>git clone https://github.com/vijayhardaha/wordpress-plugin-boilerplate.git wp-demo-plugin</pre>
**wp-demo-plugin** is just an example of plugin name. You have to write your plugin name that you want to create.

Then navigate to the folder in command line using `cd` command. Example `cd wp-demo-plugin`

Then you have to install the npm packages using this command:

<pre>npm install</pre>

Once your package installation is finish, You can setup your plugin file using this command:

<pre>npm run setup</pre>

After running this command you'll be asked to enter the plugin name. You have to use `Title Case` unique plugin name in input.
Few Examples: `WP Mail Cheker`, `Bulk Email Sender`, `WP User Importer` etc.

After a valid input, In few seconds you'll see **Complete!** message and `npm install` will be execute just after that to remove some setup package that won't be needed in your plugin development after setup is completed.

## Important Keywords

Keyword | Description
---|---
custom-plugin | Filename prefix/posfix which is used for main plugin file name and class and functions php files.
Custom Plugin | Plugin name used in main plugin file and gulp file.
Custom_Plugin | Final class name and prefix for other classes. Also used for package info in comment doc. 
CUSTOM_PLUGIN | Prefix for defined CONSTANTS
custom-plugin | Used for WordPress text-domain and enqueue script/style name prefix.
custom_plugin | Used for main function name and as other functions name prefix.

## How Setup Works

In this repositary code above **Important Keywords** are used in a way that help the setup code to match and replace the keywords with your given plugin name.
`npm run setup` is one-time command, After successfully completion, setup code and script will be removed from the `package.json` and some of packages that are used in setup will be removed as well and you'll have only have packages that are used in Plugin Development ahead.

## Plugin Structure Guide

**custom-plugin.php** is the main plugin file which contains all the plugin informations and some defined CONST and global function with main Class file instance call.

**gulpfile.js** has all all the task setup for **js, css, images, fonts**

**languages** directory contains .pot file which is useful for translation.

**includes** directory contains all the php files. Class based files will start `class-` prefix and functions files will end with `-functions.php` postfix. All the backend related files will be inside `admin` directory.

**src** directory contains all the source files for plugin assets. **js, css, images, fonts** files will be in related directory. This setup use [asset-builder](https://www.npmjs.com/package/asset-builder) package, you can read about if you have any problem with assets files set up.

**assets** directory contains all the compressed & optimized `js, css, images, fonts` files. you don't write anything in directory it will auto-generated with the help of `gulp`.

## Start Development

Run `npm start` to start development. `gulp watch` task will be started and as soon as you'll make changes on your assets files, your new assets will be generated automatically.

## Build

Run `npm run build` to build the final assets & .pot file.

## Author

Vijay Hardaha

[Peopleperhour](https://pph.me/vijayhardaha) - [Twitter](https://twitter.com/vijayhardaha)