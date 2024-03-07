<?php

namespace Verseles\Progressable\Exceptions;

use Exception;

class UniqueNameAlreadySetException extends Exception
{
  /**
   * Exception constructor.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct('The unique name has already been set.');
  }
}
