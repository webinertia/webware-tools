<?php

declare(strict_types=1);

namespace Webware\Fixtures\Acl\Repository;

use App\Fixtures\Repository\TargetAppRepository;
final class CrossRootRepository
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
