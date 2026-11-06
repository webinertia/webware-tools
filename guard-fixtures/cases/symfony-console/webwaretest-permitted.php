<?php

declare(strict_types=1);

namespace WebwareTest\Fixtures\ConsoleSupport;

use Symfony\Component\Console\Output\OutputInterface;
final class ConsolePermittedProbe
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
