<?php

declare(strict_types=1);

namespace Webware\Async\Fixtures;

use Psr\Http\Server\MiddlewareInterface;
final class PermittedRunner
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
