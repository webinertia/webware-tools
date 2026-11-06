<?php

declare(strict_types=1);

namespace App\Fixtures\Command;

use Webware\MessageBus\Command\NamedCommandInterface;
final class BadlyNamed implements NamedCommandInterface
{
}
