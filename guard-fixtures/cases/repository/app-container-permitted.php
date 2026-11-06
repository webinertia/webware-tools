<?php

declare(strict_types=1);

namespace App\Fixtures\Container;

use App\Fixtures\Repository\TargetAppRepository;
final class RepositoryPermittedFactory
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
