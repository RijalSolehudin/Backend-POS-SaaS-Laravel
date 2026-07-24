<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

use RuntimeException;

abstract class BusinessException extends RuntimeException
{
    abstract public function errorCode(): string;
}
