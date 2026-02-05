# Hyva-Themes PHP_CodeSniffer ruleset

A set of Hyvä specific PHP CodeSniffer rules extending the Magento Coding Standard.

## Installation

We NOT recommend installing the hyva-coding-standard as a dev dependency of the Magento project, because it shares some dependencies with Magento, which can easily lead to composer version conflicts.
Instead, we recommend a stand-alone installation of the hyva-coding-standard.

### Stand-alone installation

Run the following command to install the coding standard stand-alone in a directory `hyva-coding-standard`.
You can do this inside or outside the Magento project folder.
If you want to set up PHPStorm inspections and are using a remote PHP interpreter (e.g. warden), you will have to install the coding standard inside the Magento project (see below for details).
Otherwise we recommend doing this outside of your Magento project folder so it doesn't interfere with the project structure.

```sh
composer create-project --no-plugins --no-dev hyva-themes/hyva-coding-standard hyva-coding-standard
```

This is also the recommended approach for checking the coding standard in a CI pipeline.

### Installation as a dev dependency of the project

Installing the hyva-coding-standard as a project dependency is not recommended. Instead, consider installing it as stand-alone as described above.
If you still want to go ahead, because you are sure the dependencies shared with Magento have compatible versions, run the following commands:

```sh
composer require --dev hyva-themes/hyva-coding-standard
./vendor/bin/phpcs --config-set installed_paths ../../magento/magento-coding-standard,../../phpcompatibility/php-compatibilit,../../hyva-themes/hyva-coding-standard/src
```

## Command-Line Usage

Independently of how you installed the hyva-coding-standard, you can check your code by running `vendor/bin/phpcs` within the folder you installed it in.

For example, if you have a stand-alone installation of the hyva-coding-standard inside your home directory within a folder `hyva-coding-standard`, execute

```sh
~/hyva-coding-standard/vendor/bin/phpcs --standard=HyvaThemes app/code/path/to/check
```

If you installed it as a Magento project dependency, adjust the path to `phpcs` accordingly:

```sh
./vendor/bin/phpcs --standard=HyvaThemes app/code/path/to/check
```

## Configuration in PHPStorm

Setting up the PHPStorm configuration differs depending on if you use a containerized or local PHP interpreter.
The dialog details also depend on the PHPStorm version. These instructions are based on PHPStorm 2022.1.3.

### PHPStorm setup with a local PHP Interpreter

1. In the settings under PHP, ensure a local PHP interpreter is configured.
2. Under "PHP > Quality Tools", select PHP_CodeSniffer
3. Select the "Local" configuration in the dropdown
4. Click the button with the three dots beside the dropdown
5. Enter the absolute path to `vendor/bin/phpcs` and `vendor/bin/phpcbf` within your stand-alone installation folder, (for example, in my case that is `/Users/vinai/hyva-coding-standard/vendor/bin/phpcs`).
6. Click the "Validate" button, then close the dialog with "OK" if everything is configured correctly.
7. In the PHPStorm settings, navigate to "Editor > Inspections > PHP > Quality Tools"
8. Select "PHP_CodeSniffer validation"
9. Ensure the file name extension input contains "php,phtml,css"
10. In the "Coding Standard" dropdown, select "Custom"
11. Click the button with the three dots beside the dropdown
12. Enter the absolute path to the HyvaThemes ruleset in your stand-alone installation (for example, in my case that is `/Users/vinai/hyva-coding-standard/src/HyvaThemes`).
13. Click "OK" to apply the path to the coding standard, and click "OK" again to close the settings dialog.

### With PHP in docker (e.g. warden)

When using a container based PHP dev environment (for example [warden](https://warden.dev/)), PHPStorm is usually configured to run a "remote" PHP interpreter inside the container.
In this case the PHPStorm code sniffer integration is only able to work with a coding standard installation *inside* of the project folder structure. This is a limitation of PHPStorm.

This means, you will have to install the `hyva-coding-standard` stand alone inside the Magento base directory. You probably want to configure it as an excluded directory in PHPStorm, so it isn't indexed.
For example, in case of warden, the commands to create a stand-alone installation of the coding standard inside of the project director look like this:

```sh
warden shell
composer create-project --no-plugins --no-dev hyva-themes/hyva-coding-standard hyva-coding-standard
```

Then configure PHPStorm to use the coding standard using the file system paths inside the container:

1. In the settings under PHP, ensure a remote PHP interpreter is configured for the container.
2. Under "PHP > Quality Tools", select PHP_CodeSniffer
3. Select the correct remote configuration in the dropdown (e.g. "Interpreter: php-fpm" in case or warden).
4. Click the button with the three dots beside the dropdon
5. Enter the absolute path to `vendor/bin/phpcs` and `vendor/bin/phpcbf` within your stand-alone installation folder, (for example, in case of warden that is `/var/www/html/hyva-coding-standard/vendor/bin/phpcs`).
6. Click the "Validate" button, then close the dialog with "OK" if everything is configured correctly.
7. In the PHPStorm settings, navigate to "Editor > Inspections > PHP > Quality Tools"
8. Select "PHP_CodeSniffer validation"
9. Ensure the file name extension input contains "php,phtml,css"
10. In the "Coding Standard" dropdown, select "Custom"
11. Click the button with the three dots beside the dropdown
12. Enter the absolute path to the HyvaThemes ruleset in your stand-alone installation (for example, in my case that is `/var/www/html/hyva-coding-standard/src/HyvaThemes`).
13. Click "OK" to apply the path to the coding standard, and click "OK" again to close the settings dialog.

### License

This package is licensed under the **Open Software License (OSL 3.0)**.

* **Copyright:** Copyright © 2020-present Hyvä Themes. All rights reserved.
* **License Text (OSL 3.0):** The full text of the OSL 3.0 license can be found in the `LICENSE.txt` file within this package, and is also available online at [http://opensource.org/licenses/osl-3.0.php](http://opensource.org/licenses/osl-3.0.php).
