<?php

declare(strict_types=1);

namespace Webware\Traccio\Debug\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbPanel
{
    public function __construct(private Adapter $dependency)
    {
    }
}
