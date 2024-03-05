```md
# Progressable

A Laravel (not only) package to track and manage progress for different tasks or processes.

## Installation

You can install the package via composer:

```bash
composer require verseles/progressable
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Verseles\Progressable\ProgressableServiceProvider" --tag="config"
```

The contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Cache Time-to-Live
    |--------------------------------------------------------------------------
    |
    | This option specifies the default cache time-to-live (in minutes) for
    | the progress data. You can override this value for individual instances
    | of FullProgress by calling the setTtl() method.
    |
    */

    'ttl' => env('PROGRESSABLE_TTL', 1140),
];
```

## Usage

This package provides two main classes: `FullProgress` and `Progressable`.

### FullProgress

The `FullProgress` class is used to get the overall progress (sum) of all classes that implement the `Progressable` trait. It is a singleton and can be accessed through the `FullProgress` facade.

```php
use Verseles\src\Facades\FullProgress;

$fullProgress = FullProgress::make('my-progress');
echo $fullProgress->getProgress(); // Output: 50 (for example)
```

The `make` method accepts a unique name as an argument, which allows you to have multiple overall progress instances without conflicts.

### Progressable

The `Progressable` trait can be used in any class that needs to track progress. It provides two main methods: `updateProgress` and `getProgress`.

```php
use Verseles\src\Progressable;

class MyFirstTask
{
    use Progressable;

    public function __construct()
    {
        $this->setUniqueName('my-job');
    }

    public function run()
    {
        // Some task logic...
        $this->updateProgress(25);

        // More task logic...
        $this->updateProgress(75);

        // ...
    }
}
```

Since FullProgress counts the overall progress, you can add a many progressabble as you want by referencing to the same unique name:

```php
use Verseles\src\Progressable;

class MySecondTask
{
    use Progressable;

    public function __construct()
    {
        $this->setUniqueName('my-job');
    }

    public function run()
    {
        // Some task logic...
        $this->updateProgress(25);

        // More task logic...
        $this->updateProgress(75);

        // ...
    }
}
```

Use the `setUniqueName` method to associate the progress of the class with a specific overall progress instance. The `updateProgress` method updates the progress for the current instance, and the `getProgress` method retrieves the current progress.

The progress value ranges from 0 to 100.

## Testing

You can run the tests with:

```bash
make
```

## License

The Laravel Progressable package is open-sourced software licensed under the [MIT license](LICENSE.md).
