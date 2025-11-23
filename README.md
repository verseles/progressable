# Progressable

A Laravel [(not only)](#without-laravel) package to track and manage progress for different tasks or processes.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/verseles/progressable/phpunit.yml?style=for-the-badge&label=CI)

## Installation

Install the package via Composer:

```bash
composer require verseles/progressable
```

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --provider="Verseles\Progressable\ProgressableServiceProvider" --tag="config"
```

## Configuration

The published config file provides the following options:

```php
return [
    'ttl' => env('PROGRESSABLE_TTL', 1140),           // Cache TTL in minutes (default: 19 hours)
    'prefix' => env('PROGRESSABLE_PREFIX', 'progressable'), // Cache key prefix
    'precision' => env('PROGRESSABLE_PRECISION', 2),  // Default decimal precision
];
```

## Usage

This package provides a main trait: `Progressable`.

### With Laravel

The `Progressable` trait can be used in any class that needs to track progress.

"Local" refers to the progress of your class/model/etc, while "Overall" represents the average of all Progressable instances using the same unique name.

### Basic Example

```php
use Verseles\Progressable\Progressable;

class MyFirstTask
{
    use Progressable;

    public function __construct()
    {
        $this->setOverallUniqueName('my-job')->resetOverallProgress();
    }

    public function run()
    {
        foreach (range(1, 100) as $value) {
            $this->setLocalProgress($value);
            echo "Overall Progress: " . $this->getOverallProgress() . "%" . PHP_EOL;
        }
    }
}
```

### Available Methods

#### Progress Management

| Method | Description |
|--------|-------------|
| `setOverallUniqueName(string $name)` | Set the progress group identifier |
| `setLocalProgress(float $progress)` | Set progress for this instance (0-100) |
| `getLocalProgress(?int $precision)` | Get current progress (default precision from config) |
| `incrementLocalProgress(float $amount = 1)` | Increment progress by amount (can be negative) |
| `resetLocalProgress()` | Reset instance progress to 0 |
| `getOverallProgress(?int $precision)` | Get average progress of all instances |
| `resetOverallProgress()` | Clear all progress data for the group |
| `removeLocalFromOverall()` | Remove this instance from overall calculation |

#### Status Checks

| Method | Description |
|--------|-------------|
| `isComplete()` | Check if local progress is 100% |
| `isOverallComplete()` | Check if overall progress is 100% |

#### Configuration

| Method | Description |
|--------|-------------|
| `setTTL(int $minutes)` | Set cache time-to-live |
| `getTTL()` | Get current TTL |
| `setPrecision(int $decimals)` | Set decimal precision for progress values |
| `getPrecision()` | Get current precision |
| `setPrefixStorageKey(string $prefix)` | Set custom cache key prefix (before setting unique name) |
| `setLocalKey(string $key)` | Set custom identifier for this instance |
| `getLocalKey()` | Get current instance identifier |

#### Custom Storage (for non-Laravel usage)

| Method | Description |
|--------|-------------|
| `setCustomSaveData(callable $callback)` | Set custom save callback |
| `setCustomGetData(callable $callback)` | Set custom get callback |

### Increment Example

```php
use Verseles\Progressable\Progressable;

class FileProcessor
{
    use Progressable;

    public function process(array $files)
    {
        $this->setOverallUniqueName('file-processing')->resetOverallProgress();
        $increment = 100 / count($files);

        foreach ($files as $file) {
            $this->processFile($file);
            $this->incrementLocalProgress($increment);

            if ($this->isComplete()) {
                echo "Processing complete!";
            }
        }
    }
}
```

### Without Laravel

You can use the `Progressable` trait without Laravel by providing custom save and get data callbacks:

```php
use Verseles\Progressable\Progressable;

$storage = [];

$saveCallback = function ($key, $data, $ttl) use (&$storage) {
    $storage[$key] = $data;
};

$getCallback = function ($key) use (&$storage) {
    return $storage[$key] ?? [];
};

$obj = new class { use Progressable; };
$obj
    ->setCustomSaveData($saveCallback)
    ->setCustomGetData($getCallback)
    ->setOverallUniqueName('my-task')
    ->resetOverallProgress()
    ->setLocalProgress(25);

echo $obj->getLocalProgress(); // 25
```

## Testing

```bash
# Run tests
composer test

# Check code style
composer lint:test

# Fix code style
composer lint

# Run static analysis
composer analyse
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## License

The Progressable package is open-sourced software licensed under the [MIT license](./LICENSE.md).
