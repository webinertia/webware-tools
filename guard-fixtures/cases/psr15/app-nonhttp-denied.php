<?php

declare(strict_types=1);

namespace App\Fixtures\Psr15\Service;

use Psr\Http\Server\MiddlewareInterface;
final class DeniedService
{
    public function __construct(private MiddlewareInterface $dependency)
    {
    }
}
