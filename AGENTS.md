# Guidelines for Working with Popup Maker Repository

This repository is a WordPress plugin written in PHP and JavaScript. Follow these instructions when making changes.

## Project Structure
- `classes/` contains the main PHP classes for the plugin logic and admin screens.
- `includes/` holds compatibility helpers, field definitions and procedural code.
- `src/` is the source for JavaScript modules used in the block editor.
- `assets/` stores compiled CSS/JS and static assets like images.
- `tasks/` defines Gulp build scripts.
- `templates/` contains markup templates used for popup output.

## Environment Setup
- Install PHP dependencies with `composer install`.
- Use Node.js `13` (`.nvmrc` indicates version 13) and run `npm install` for JS dependencies.

## Code Style
- **PHP**: Adhere to WordPress Coding Standards. Run `composer lint` to check and `composer format` to auto-fix.
- **PHP static analysis**: Run `composer phpstan`.
- **JavaScript**: Run `npm run lint:js` for JS and `npm run lint:style` for CSS/SCSS. Prettier config comes from `@wordpress/prettier-config`.

## Testing
- Run PHP unit tests with `composer tests`. They use the configuration from `phpunit.xml.dist`.
- Some tests may rely on the WordPress test suite installed via `bin/install-wp-tests.sh`.

## Build & Releases
- Development assets live in `src` and `assets/js/src`. Use `npm run build` or `gulp` tasks to build compiled assets.
- Release packages can be created with `npm run zip:release`.

## Repository Rules
- Do **not** commit changes to `package-lock.json`.

## Documentation
- `readme.md` explains how to get started and links to the wiki for more advanced topics.
- `CHANGELOG.md` tracks notable changes between releases.

## Commit Messages
Follow the commit style from `.github/CONTRIBUTING.md`:
1. Subject line up to 50 characters.
2. Capitalize the subject.
3. Use the imperative mood.
4. Separate subject from body with a blank line.
5. Explain what and why, not how.
6. Reference related issues when applicable.

## Pull Requests
- Use the template in `.github/PULL_REQUEST_TEMPLATE.md`.
- Ensure your code passes PHPCS, StyleLint, ESLint, and PHPUnit tests before submitting.


