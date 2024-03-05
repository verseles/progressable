<?php

namespace Verseles\Progressable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Verseles\Progressable\FullProgress
 */
class FullProgress extends Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor(): string
  {
    return 'full-progress';
  }
}
