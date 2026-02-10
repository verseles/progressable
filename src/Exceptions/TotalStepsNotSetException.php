<?php

namespace Verseles\Progressable\Exceptions;

use Exception;

class TotalStepsNotSetException extends Exception {
    /**
     * Exception constructor.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct('You must set the total steps before advancing progress');
    }
}
