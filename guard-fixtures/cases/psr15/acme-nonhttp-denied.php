<?php

declare(strict_types=1);

namespace Acme\Blog\Service;

use Psr\Http\Server\MiddlewareInterface;
final class DeniedService
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
