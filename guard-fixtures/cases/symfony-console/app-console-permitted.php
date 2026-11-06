<?php

declare(strict_types=1);

namespace App\Fixtures\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'fixture')]
final class SymfonyPermittedCommand
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
