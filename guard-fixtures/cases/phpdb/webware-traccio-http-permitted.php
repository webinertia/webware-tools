<?php

declare(strict_types=1);

namespace Webware\Traccio\Http\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbReporter
{
    public function __construct(private Adapter $dependency)
    {
    }
}
