<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Time-to-Live
    |--------------------------------------------------------------------------
    |
    | This option specifies the default cache time-to-live (in minutes) for
    | the progress data. You can override this value for individual instances
    | of Progressable by calling the setTTL() method.
    |
    */

    'ttl' => env('PROGRESSABLE_TTL', 1140),

    /*
    |--------------------------------------------------------------------------
    | Cache Prefix
    |--------------------------------------------------------------------------
    |
    | This option specifies the default prefix for the progress data.
    |
    */

    'prefix' => env('PROGRESSABLE_PREFIX', 'progressable'),

    /*
    |--------------------------------------------------------------------------
    | Default Precision
    |--------------------------------------------------------------------------
    |
    | This option specifies the default precision (decimal places) for
    | progress values. You can override this per-call by passing a precision
    | parameter to getLocalProgress() or getOverallProgress().
    |
    */

    'precision' => env('PROGRESSABLE_PRECISION', 2),
];
