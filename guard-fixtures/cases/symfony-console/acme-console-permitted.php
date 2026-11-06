<?php

declare(strict_types=1);

namespace Acme\Blog\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'fixture')]
final class SymfonyPermittedCommand
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
