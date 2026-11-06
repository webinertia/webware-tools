<?php

declare(strict_types=1);

namespace Webware\Acl;

use Webware\Fixtures\Repository\TargetRepository;
final class RepositoryWiring
{
    public function __construct(private TargetRepository $dependency)
    {
    }
}
