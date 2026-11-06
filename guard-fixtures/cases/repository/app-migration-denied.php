<?php

declare(strict_types=1);

namespace App\Fixtures\Migration;

use App\Fixtures\Repository\TargetAppRepository;
final class RepositoryDeniedRunner
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
