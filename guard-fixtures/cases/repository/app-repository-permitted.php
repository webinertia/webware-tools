<?php

declare(strict_types=1);

namespace App\Fixtures\Repository;

use App\Fixtures\Repository\TargetAppRepository;
final class PermittedRepository
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
