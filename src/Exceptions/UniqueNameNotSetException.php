<?php

namespace Verseles\Progressable\Exceptions;

use Exception;

class UniqueNameNotSetException extends Exception {
    /**
     * Exception constructor.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct('You must set a unique name before updating progress');
    }
}
