<?php

declare(strict_types=1);

namespace MyTestApp\Fixtures\Psr15;

use Psr\Http\Server\MiddlewareInterface;
final class DeniedProbe
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
