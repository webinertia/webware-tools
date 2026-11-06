<?php

declare(strict_types=1);

namespace Acme\Blog\Repository;

use Webware\MessageBus\MessageBus;
final class BusDeniedRepository
{
    public function __construct(private MessageBus $dependency)
    {
    }
}
