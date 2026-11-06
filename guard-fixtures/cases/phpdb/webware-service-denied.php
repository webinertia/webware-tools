<?php

declare(strict_types=1);

namespace Webware\Fixtures\Service;

use PhpDb\Adapter\Adapter;
final class PhpDbDeniedService
{
    public function __construct(private Adapter $dependency)
    {
    }
}
