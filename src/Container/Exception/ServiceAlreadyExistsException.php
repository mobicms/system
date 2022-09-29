<?php

declare(strict_types=1);

namespace Mobicms\Container\Exception;

class ServiceAlreadyExistsException extends ContainerException
{
    public function __construct(string $name)
    {
        parent::__construct('Service "' . $name . '" already exists.');
    }
}
