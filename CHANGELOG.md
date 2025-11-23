# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `incrementLocalProgress(float $amount = 1)` - Increment progress by a given amount
- `isComplete()` - Check if local progress is 100%
- `isOverallComplete()` - Check if overall progress is 100%
- `removeLocalFromOverall()` - Remove instance from overall progress calculation
- `setPrecision(int $precision)` - Set custom precision for progress values
- `getPrecision()` - Get current precision setting
- Configurable default precision via `config/progressable.php`
- PHPStan static analysis (level 5) with Larastan
- Laravel Pint code style enforcement
- Comprehensive test suite with 23+ tests
- CI pipeline with lint, analyse, and test jobs
- CHANGELOG.md and CONTRIBUTING.md documentation

### Changed
- `getLocalProgress()` now accepts `null` to use configured default precision
- `getOverallProgress()` now accepts `null` to use configured default precision
- Improved test isolation with unique test IDs
- Optimized `setLocalKey()` to avoid duplicate storage calls

### Fixed
- README.md incorrect method names (`updateLocalProgress` -> `setLocalProgress`)
- README.md incorrect imports (`use Verseles\Progressable` -> `use Verseles\Progressable\Progressable`)
- Config comment referencing wrong class name (`FullProgress` -> `Progressable`)
- Added missing return type to `getOverallUniqueName()`

## [1.0.0] - Previous Release

### Added
- Initial release with core progress tracking functionality
- `setOverallUniqueName()` - Set progress group identifier
- `setLocalProgress()` - Update instance progress
- `getLocalProgress()` - Get instance progress
- `getOverallProgress()` - Get average progress of all instances
- `resetLocalProgress()` - Reset instance to 0
- `resetOverallProgress()` - Clear all progress data
- `setCustomSaveData()` / `setCustomGetData()` - Custom storage callbacks
- `setTTL()` / `getTTL()` - Cache TTL configuration
- `setPrefixStorageKey()` - Custom cache key prefix
- `setLocalKey()` / `getLocalKey()` - Custom instance identifiers
- Laravel service provider with auto-discovery
- Support for Laravel 11 and 12
- PHP 8.4 requirement
