# Progressable 🚀

A Laravel [(not only)](#without-laravel) package to track and manage progress for different tasks or processes.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/verseles/progressable/phpunit.yml?style=for-the-badge&label=PHPUnit)

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
  'ttl' => env('PROGRESSABLE_TTL', 1140), // Default cache time-to-live (in minutes)

  'prefix' => env('PROGRESSABLE_PREFIX', 'progressable'), // Cache key prefix
];
```

## Usage

This package provides a main trait: `Progressable`.

### With Laravel

The `Progressable` trait can be used in any class that needs to track progress. It provides two main methods: `setLocalProgress` and `getLocalProgress`.

"Local" refers to the progress of your class/model/etc, while "Overall" represents the sum of all Progressable classes using the same key name.

### Example
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
            usleep(100000); // Sleep for 100 milliseconds
            echo "Overall Progress: " . $this->getOverallProgress() . "%" . PHP_EOL;
        }
    }
}
```

```php
use Verseles\Progressable\Progressable;

class MySecondTask
{
    use Progressable;

    public function __construct()
    {
        $this->setOverallUniqueName('my-job');
    }

    public function run()
    {
        foreach (range(1, 100) as $value) {
            $this->setLocalProgress($value);
            usleep(100000); // Sleep for 100 milliseconds
            echo "Overall Progress: " . $this->getOverallProgress() . "%" . PHP_EOL;
        }
    }
}
```

- Use `setOverallUniqueName` to associate the progress with a specific overall progress instance.
- `setLocalProgress` updates the progress for the current instance.
- `getLocalProgress` retrieves the current progress.
- `getOverallProgress` retrieves the overall progress data.
- `resetOverallProgress` resets the overall progress (recommended after setting the unique name for the first time).

The progress value ranges from 0 to 100.

### Without Laravel

You can use the `Progressable` trait without Laravel by providing custom save and get data methods.

### Example

```php
$overallUniqueName = 'test-without-laravel';

$my_super_storage = [];

$saveCallback = function ($key, $data, $ttl) use (&$my_super_storage) {
  $my_super_storage[$key] = $data;
};

$getCallback = function ($key) use (&$my_super_storage) {
  return $my_super_storage[$key] ?? [];
};


$obj1 = new class { use Progressable; };
$obj1
  ->setCustomSaveData($saveCallback)
  ->setCustomGetData($getCallback)
  ->setOverallUniqueName($overallUniqueName)
  ->resetOverallProgress()
  ->setLocalProgress(25);

$obj2 = new class { use Progressable; };
$obj2
  ->setCustomSaveData($saveCallback)
  ->setCustomGetData($getCallback)
  ->setOverallUniqueName($overallUniqueName)
  ->setLocalProgress(75);

```

## Testing

To run the tests, execute the following command:

```bash
make
```

## License

The Progressable package is open-sourced software licensed under the [MIT license](./LICENSE.md).
