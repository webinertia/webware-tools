<?php

declare(strict_types=1);

namespace App\Fixtures\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Fixtures\Repository\TargetAppRepository;
#[AsCommand(name: 'fixture')]
final class RepositoryPermittedCommand
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
