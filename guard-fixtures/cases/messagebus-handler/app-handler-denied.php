<?php

declare(strict_types=1);

namespace App\Fixtures\Http\RequestHandler;

use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;
final class BusDeniedHandler implements RequestHandlerInterface
{
    public function __construct(private MessageBusInterface $dependency)
    {
    }
}
