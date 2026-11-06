<?php

declare(strict_types=1);

namespace Acme\Blog\Http\Service;

use Psr\Http\Server\MiddlewareInterface;
final class PermittedService
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
