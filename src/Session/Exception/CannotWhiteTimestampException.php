<?php

declare(strict_types=1);

namespace Mobicms\Session\Exception;

use RuntimeException;

class CannotWhiteTimestampException extends RuntimeException
{
    public function __construct(string $file)
    {
        parent::__construct('Cannot white session GC timestamp file ' . $file);
    }
}
