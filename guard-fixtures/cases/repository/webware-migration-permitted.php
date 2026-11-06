<?php

declare(strict_types=1);

namespace Webware\Migration\Fixtures;

use Webware\Fixtures\Repository\TargetRepository;
final class SchemaRunner
{
    public function __construct(private TargetRepository $dependency)
    {
    }
}
