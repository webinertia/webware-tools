<?php

declare(strict_types=1);

namespace Webware\Fixtures\Entity;

use PhpDb\Adapter\Adapter;
final class PermittedEntity
{
    public function __construct(private Adapter $dependency)
    {
    }
}
