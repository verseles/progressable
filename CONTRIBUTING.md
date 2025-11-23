# Contributing to Progressable

First off, thank you for considering contributing to Progressable!

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/verseles/progressable.git
cd progressable
```

2. Install dependencies:
```bash
composer install
```

3. Run tests to make sure everything works:
```bash
composer test
```

## Code Quality Tools

This project uses several tools to maintain code quality:

### PHPUnit Tests
```bash
composer test
```

### Laravel Pint (Code Style)
```bash
# Check for style issues
composer lint:test

# Fix style issues automatically
composer lint
```

### PHPStan (Static Analysis)
```bash
composer analyse
```

## Pull Request Process

1. Fork the repository and create your branch from `main`
2. Make your changes
3. Ensure all tests pass: `composer test`
4. Ensure code style is correct: `composer lint:test`
5. Ensure static analysis passes: `composer analyse`
6. Update documentation if needed (README.md, CHANGELOG.md)
7. Submit your pull request

## Coding Standards

- Follow PSR-12 coding standards (enforced by Laravel Pint)
- Write tests for new features
- Keep methods focused and small
- Use descriptive variable and method names
- Add PHPDoc blocks for public methods

## Adding New Features

When adding new features:

1. Add the feature implementation in `src/Progressable.php`
2. Add tests in `tests/ProgressableTest.php`
3. Update `README.md` with usage examples
4. Add entry to `CHANGELOG.md` under `[Unreleased]`

## Reporting Bugs

When reporting bugs, please include:

- PHP version
- Laravel version (if applicable)
- Progressable version
- Steps to reproduce
- Expected behavior
- Actual behavior

## Questions?

Feel free to open an issue for any questions about contributing.
