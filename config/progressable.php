<?php

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

  /*
  |--------------------------------------------------------------------------
  | Cache Prefix
  |--------------------------------------------------------------------------
  |
  | This option specifies the default prefix for the progress data.
  |
  */

  'prefix' => env('PROGRESSABLE_PREFIX', 'progressable'),
];
