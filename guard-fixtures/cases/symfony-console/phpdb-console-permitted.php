<?php

declare(strict_types=1);

namespace PhpDb\Session\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'fixture')]
final class OutputCommand
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
