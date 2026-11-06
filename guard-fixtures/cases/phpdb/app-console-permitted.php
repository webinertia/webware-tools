<?php

declare(strict_types=1);

namespace App\Fixtures\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use PhpDb\Adapter\Adapter;
#[AsCommand(name: 'fixture')]
final class PhpDbPermittedCommand
{
    public function __construct(private Adapter $dependency)
    {
    }
}
