<?php

declare(strict_types=1);

namespace Acme\Blog\Service;

use Acme\Blog\Repository\TargetAcmeRepository;
final class RepositoryDeniedService
{
    public function __construct(private TargetAcmeRepository $dependency)
    {
    }
}
