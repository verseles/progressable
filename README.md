# Progressable

A Laravel [(not only)](#without-laravel) package to track and manage progress for different tasks or processes.

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

```php
use Verseles\src\Progressable;

class MyFirstTask
{
    use Progressable;

    public function __construct()
    {
        $this->setOverallUniqueName('my-job');
    }

    public function run()
    {
        // Some task logic...
        $this->updateLocalProgress(25);

        // More task logic...
        $this->updateLocalProgress(75);

        // ...
    }
}
```

```php
use Verseles\src\Progressable;

class MySecondTask
{
    use Progressable;

    public function __construct()
    {
        $this->setOverallUniqueName('my-job');
    }

    public function run()
    {
        // Some task logic...
        $this->updateLocalProgress(25);

        // More task logic...
        $this->updateLocalProgress(75);

        // ...
    }
}
```

Use the `setOverallUniqueName` method to associate the progress of the class with a specific overall progress instance. The `updateLocalProgress` method updates the progress for the current instance, and the `getLocalProgress` method retrieves the current progress. The `getOverallProgress` method retrieves the overall progress data.

The progress value ranges from 0 to 100.

### Without Laravel

You can use the `Progressable` trait without Laravel. You need to provide a custom save and get data methods.

Here is a very imaginary example:

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

The Laravel Progressable package is open-sourced software licensed under the [MIT license](./LICENSE.md).
