<?php

declare(strict_types=1);

namespace Webware\Traccio\PhpDb\Fixtures;

use PhpDb\Adapter\Adapter;
final class PhpDbDelegator
{
    public function __construct(private Adapter $dependency)
    {
    }
}
