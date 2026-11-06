<?php

declare(strict_types=1);

namespace Webware\Log\Handler\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbLogHandler
{
    public function __construct(private Adapter $dependency)
    {
    }
}
