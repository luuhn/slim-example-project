<?php

namespace App\Domain\Exceptions;

/**
 * 401 Unauthorized when trying to access page or data without being logged in
 * @package App\Domain\Exceptions
 */
class UnauthorizedException extends \RuntimeException
{

    public function __construct($message)
    {
        parent::__construct($message);
    }
}
