<?php

declare(strict_types=1);

namespace Acme\Blog\Service;

use PhpDb\Adapter\Adapter;
final class PhpDbDeniedService
{
    public function __construct(private Adapter $dependency)
    {
    }
}
