<?php

declare(strict_types=1);

namespace App\Fixtures\Repository;

use PhpDb\Adapter\Adapter;
final class PhpDbPermittedRepository
{
    public function __construct(private Adapter $dependency)
    {
    }
}
