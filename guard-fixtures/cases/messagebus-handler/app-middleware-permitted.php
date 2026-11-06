<?php

declare(strict_types=1);

namespace App\Fixtures\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Webware\MessageBus\MessageBusInterface;
final class BusAccessMiddleware implements MiddlewareInterface
{
    public function __construct(private MessageBusInterface $dependency)
    {
    }
}
