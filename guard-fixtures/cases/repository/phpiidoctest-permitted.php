<?php

declare(strict_types=1);

namespace PhpiidocTest\Fixtures\Repository;

use App\Fixtures\Repository\TargetAppRepository;
final class RepositoryPermittedProbe
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
