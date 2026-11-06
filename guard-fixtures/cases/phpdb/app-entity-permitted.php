<?php

declare(strict_types=1);

namespace App\Fixtures\Entity;

use PhpDb\Adapter\Adapter;
final class PermittedEntity
{
    public function __construct(private Adapter $dependency)
    {
    }
}
