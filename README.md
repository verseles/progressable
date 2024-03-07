```
# Progressable 

A Laravel [(not only)](#without-laravel) package to track and manage progress for different tasks or processes.

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/verseles/progressable/phpunit.yml?style=for-the-badge&label=PHPUnit)

## Installation

You can install the package via composer:

```bash
composer require verseles/progressable
```

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --provider="Verseles\Progressable\ProgressableServiceProvider" --tag="config"
```

The options of the published config file:

```php
return [
  'ttl' => env('PROGRESSABLE_TTL', 1140),

  'prefix' => env('PROGRESSABLE_PREFIX', 'progressable'),
];
```

## Usage

This package provides a main class (a trait): `Progressable`.

### With Laravel

The `Progressable` trait can be used in any class that needs to track progress. It provides two main methods: `updateLocalProgress` and `getLocalProgress`.

"Local" because anytime you can get "Overall" progress calling `getOverallProgressData()`. Local is your class/model/etc progress and Overall is the sum of all Progressable classes using the same key name.

### Example
```php
use Verseles\Progressable;

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
use Verseles\Progressable;

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

* Use the `setOverallUniqueName` method to associate the progress of the class with a specific overall progress instance
* `setLocalProgress` method updates the progress for the current instance.
* `getLocalProgress` method retrieves the current progress.
* `getOverallProgress` method retrieves the overall progress data.
* `resetOverallProgress` is good to be called after set unique name for the first time in your first class if your progress will run more then once to avoid wrong calculations in cache.

The progress value ranges from 0 to 100.

### Without Laravel

You can use the `Progressable` trait without Laravel. You need to provide a custom save and get data methods.

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
  ->updateLocalProgress(25);

$obj2 = new class { use Progressable; };
$obj2
  ->setCustomSaveData($saveCallback)
  ->setCustomGetData($getCallback)
  ->setOverallUniqueName($overallUniqueName)
  ->updateLocalProgress(75);

```

## Testing

You can run the tests with:

```bash
make
```

## License

The Progressable package is open-sourced software licensed under the [MIT license](./LICENSE.md).
```
