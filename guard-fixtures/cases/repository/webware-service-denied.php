<?php

declare(strict_types=1);

namespace Webware\Fixtures\Service;

use Webware\Fixtures\Repository\TargetRepository;
final class RepositoryDeniedService
{
    public function __construct(private TargetRepository $dependency)
    {
    }
}
