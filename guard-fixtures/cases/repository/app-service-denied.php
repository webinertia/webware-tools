<?php

declare(strict_types=1);

namespace App\Fixtures\Service;

use App\Fixtures\Repository\TargetAppRepository;
final class RepositoryDeniedService
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
