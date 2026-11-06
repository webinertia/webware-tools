<?php

declare(strict_types=1);

namespace App\Fixtures\QueryHandler;

use Webware\MessageBus\QueryHandlerInterface;
use Webware\MessageBus\MessageBus;
final class BusPermittedHandler implements QueryHandlerInterface
{
    public function __construct(private MessageBus $dependency)
    {
    }
}
