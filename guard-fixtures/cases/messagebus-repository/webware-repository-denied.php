<?php

declare(strict_types=1);

namespace Webware\Fixtures\Repository;

use Webware\MessageBus\MessageBus;
final class BusDeniedRepository
{
    public function __construct(private MessageBus $dependency)
    {
    }
}
