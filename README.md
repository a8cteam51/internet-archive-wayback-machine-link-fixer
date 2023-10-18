# Team 51 Plugin Scaffold

Welcome to the Team 51 Plugin Scaffold, a standardized starting point for creating new WordPress plugins for Team 51. This repository contains the necessary files and structure to ensure a consistent approach when developing new plugins.

## Getting Started

To begin, run the command `team51 create-repository --repo-type=plugin`

If you don't want to create a repository for your plugin, another option is to clone or download this repository. Rename the folder and the main PHP file with your desired plugin name. Be sure to follow the naming convention: plugin-name for the folder and plugin-name.php for the main PHP file.

## Configuration

You'll need to update the following fields in the main PHP file's header:

- Plugin Name: The name of your plugin
- Plugin URI: The URL of the plugin's repository
- Description: A brief description of the plugin's functionality.

## Folder Structure

This scaffold has the following folder structure:

```
plugin-name/
├── assets/
│   ├── css/
│   │   ├── build/
│   │   └── src/
│   ├── js/
│   │   ├── build/
│   │   └── src/
│   └── images/
├── blocks/
│   ├── build/
│   └── src/
├── includes/
├── languages/
├── models/
├── src/
│   ├ ...
│   └── Integrations/
├── templates/
│   ├ ...
│   └── admin/
└── plugin-name.php
```

- assets: A folder to store all static assets such as styles, scripts, and images.
- blocks: A folder for storing Gutenberg block files, if the plugin uses custom blocks.
- includes: Contains any PHP files with additional functionality for the plugin. Mostly useful for helper functions.
- languages: Contains the translation files for your plugin.
- models: Contains PHP classes or data models that represent the plugin's data structures. As an example, think of WooCommerce's `WC_Order` class.
- src: A folder for organizing the plugin's main PHP classes or code components, such as integrations with other plugins or services. These classes should be organized into subfolders following the [PSR-4](https://www.php-fig.org/psr/psr-4/) convention. `Composer` will handle the autoloading for these classes.
- templates: Contains any PHP template files used for rendering HTML output. Admin templates should generally be in their own folder separated from front-end templates.
- plugin-name.php: The main PHP file containing the plugin header and bootstraping functionality.

## Development

Develop your plugin by adding the necessary functionality by creating new files within the includes folder. Remember to enqueue your styles and scripts within the assets folder.

Follow the WordPress Coding Standards for PHP, CSS, and JavaScript when writing your code. You can read more about linting and formatting your code in the [Team51 Project Scaffold](https://github.com/a8cteam51/team51-project-scaffold#code-style--quality).

## Documentation

As you develop your plugin, update the README.md file with detailed information about your plugin's features, usage, installation, and any other pertinent information.

## Testing

If your plugin is WooCommerce specific, it should be tested with the Storefront theme and latest default theme. If it's a general plugin, it should be tested with the latest default theme as well as Twenty Twenty-One (a non-FSE theme).
