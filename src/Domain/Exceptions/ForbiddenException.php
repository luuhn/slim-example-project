<?php

namespace App\Domain\Exceptions;

/**
 * Class ForbiddenException when user tries to access forbidden area or function
 * @package App\Domain\Exceptions
 */
class ForbiddenException extends \RuntimeException
{

    public function __construct($message)
    {
        parent::__construct($message);
    }
}
