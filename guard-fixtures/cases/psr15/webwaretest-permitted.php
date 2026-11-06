<?php

declare(strict_types=1);

namespace WebwareTest\Fixtures\Psr15;

use Psr\Http\Server\MiddlewareInterface;
final class PermittedProbe
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
