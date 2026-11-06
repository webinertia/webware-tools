<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Fixtures\Repository;

use Webware\Fixtures\Repository\TargetRepository;
final class RepositoryPermittedProbe
{
    public function __construct(private TargetRepository $dependency)
    {
    }
}
