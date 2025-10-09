<?php

declare(strict_types=1);

namespace Mobicms\System\Session\Exception;

use RuntimeException;

final class CannotWhiteTimestampException extends RuntimeException
{
    public function __construct(string $file)
    {
        parent::__construct('Cannot white session GC timestamp file ' . $file);
    }
}
