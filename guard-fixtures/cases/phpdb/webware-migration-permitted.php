<?php

declare(strict_types=1);

namespace Webware\Migration\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbSchemaRunner
{
    public function __construct(private Adapter $dependency)
    {
    }
}
