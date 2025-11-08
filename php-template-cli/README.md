# PHP Template CLI

## Overview

This project is a command-line interface (CLI) tool designed to process template files located in a specified directory. It reads templates, fills out variables, and saves the processed files. The tool is built using PHP and follows a modular architecture.

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd php-template-cli
   ```

2. Install dependencies using Composer:
   ```
   composer install
   ```

## Usage

To use the CLI tool, run the following command:

```
php bin/template-processor <options>
```

### Options

- `--directory`: Specify the path to the folder containing the template files. Default is `/template/packs`.
- `--output`: Specify the output directory for the processed files. Default is the same as the input directory.

### Example

To process templates in the `/template/packs` directory and save the output in the same directory, use:

```
php bin/template-processor --directory=/template/packs
```

## Structure

- `bin/template-processor`: The executable script for the CLI program.
- `src/Command/ProcessCommand.php`: Contains the `ProcessCommand` class for executing the template processing command.
- `src/Template/Processor.php`: Responsible for reading and processing template files.
- `src/Utils/VariablesResolver.php`: Provides methods for resolving and replacing variables in templates.
- `config/services.php`: Contains service definitions for dependency injection.
- `tests/ProcessorTest.php`: Unit tests for the `Processor` class.
- `composer.json`: Composer configuration file.
- `phpunit.xml`: PHPUnit configuration file.
- `.gitignore`: Specifies files to be ignored by Git.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License

This project is licensed under the MIT License. See the LICENSE file for details.