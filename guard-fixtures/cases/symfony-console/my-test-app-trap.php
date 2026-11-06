<?php

declare(strict_types=1);

namespace MyTestApp\Fixtures\ConsoleSupport;

use Symfony\Component\Console\Output\OutputInterface;
final class ConsoleTrap
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
