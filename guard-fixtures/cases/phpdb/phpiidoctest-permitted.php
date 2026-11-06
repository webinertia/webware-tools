<?php

declare(strict_types=1);

namespace PhpiidocTest\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbPermittedProbe
{
    public function __construct(private Adapter $dependency)
    {
    }
}
