<?php

declare(strict_types=1);

namespace Webware\Router\Fixtures;

use Psr\Http\Server\MiddlewareInterface;
final class PermittedRouter
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
