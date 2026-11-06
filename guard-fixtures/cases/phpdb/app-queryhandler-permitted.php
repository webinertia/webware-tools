<?php

declare(strict_types=1);

namespace App\Fixtures\QueryHandler;

use Webware\MessageBus\QueryHandlerInterface;
use PhpDb\Adapter\Adapter;
final class PhpDbPermittedHandler implements QueryHandlerInterface
{
    public function __construct(private Adapter $dependency)
    {
    }
}
