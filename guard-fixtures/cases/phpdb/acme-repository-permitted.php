<?php

declare(strict_types=1);

namespace Acme\Blog\Repository;

use PhpDb\Adapter\Adapter;
final class PhpDbPermittedRepository
{
    public function __construct(private Adapter $dependency)
    {
    }
}
