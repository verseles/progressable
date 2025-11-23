# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-01-XX

### Added
- **Metadata Support**: Store additional data with progress
  - `setMetadata(array $metadata)` - Set metadata array
  - `getMetadata()` - Get all metadata
  - `addMetadata(string $key, mixed $value)` - Add single metadata value
  - `getMetadataValue(string $key, mixed $default)` - Get single metadata value
- **Status Messages**: Attach status messages to progress
  - `setStatusMessage(?string $message)` - Set status message
  - `getStatusMessage()` - Get status message
- **Event Callbacks**: React to progress changes
  - `onProgressChange(callable $callback)` - Called on progress change with `(float $new, float $old, static $instance)`
  - `onComplete(callable $callback)` - Called when progress reaches 100% with `(static $instance)`
- **Progress Helpers**:
  - `incrementLocalProgress(float $amount = 1)` - Increment/decrement progress
  - `isComplete()` - Check if local progress is 100%
  - `isOverallComplete()` - Check if overall progress is 100%
  - `removeLocalFromOverall()` - Remove instance from overall calculation
- **Precision Configuration**:
  - `setPrecision(int $precision)` - Set decimal precision
  - `getPrecision()` - Get current precision
  - Configurable default precision via `config/progressable.php`
- **CI/CD Improvements**:
  - PHPStan static analysis (level 5) with Larastan
  - Laravel Pint code style enforcement
  - Code coverage with pcov and Codecov integration
  - Parallel CI jobs for lint, analyse, and test
- **Documentation**:
  - CHANGELOG.md with full history
  - CONTRIBUTING.md with guidelines
  - Comprehensive README with API tables
  - Codecov badge in README

### Changed
- `getLocalProgress()` now accepts `?int` (null uses configured default precision)
- `getOverallProgress()` now accepts `?int` (null uses configured default precision)
- Progress data now stores metadata and messages alongside progress value
- Improved test suite with 35 tests and 70+ assertions

### Fixed
- README.md incorrect method names (`updateLocalProgress` -> `setLocalProgress`)
- README.md incorrect imports (`use Verseles\Progressable` -> `use Verseles\Progressable\Progressable`)
- Config comment referencing wrong class name (`FullProgress` -> `Progressable`)
- Added missing return type to `getOverallUniqueName()`
- Optimized `setLocalKey()` to avoid duplicate storage calls

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
