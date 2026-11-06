<?php

declare(strict_types=1);

namespace PhpDb\Fixtures;

use PhpDb\Adapter\Adapter;
final class Wiring
{
    public function __construct(private Adapter $dependency)
    {
    }
}
