<?php

declare(strict_types=1);

namespace Mobicms\System\Config\Exception;

use InvalidArgumentException;

final class KeyAlreadyExistsException extends InvalidArgumentException
{
    public function __construct(string $key)
    {
        parent::__construct('This key "' . $key . '" already exists');
    }
}
