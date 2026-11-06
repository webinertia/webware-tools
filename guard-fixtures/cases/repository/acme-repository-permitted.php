<?php

declare(strict_types=1);

namespace Acme\Blog\Repository;

use Webware\Fixtures\Repository\TargetRepository;
final class PermittedRepository
{
    public function __construct(private TargetRepository $dependency)
    {
    }
}
