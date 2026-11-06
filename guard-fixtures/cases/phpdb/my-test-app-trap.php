<?php

declare(strict_types=1);

namespace MyTestApp\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbTrap
{
    public function __construct(private Adapter $dependency)
    {
    }
}
