<?php

declare(strict_types=1);

namespace Webware\Acl;

use PhpDb\Adapter\Adapter;
final class PhpDbWiring
{
    public function __construct(private Adapter $dependency)
    {
    }
}
