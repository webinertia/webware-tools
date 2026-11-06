<?php

declare(strict_types=1);

namespace App\Fixtures\CommandHandler;

use Webware\MessageBus\CommandHandlerInterface;
use PhpDb\Adapter\Adapter;
final class PhpDbPermittedHandler implements CommandHandlerInterface
{
    public function __construct(private Adapter $dependency)
    {
    }
}
