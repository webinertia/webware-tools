<?php

declare(strict_types=1);

namespace App\Fixtures\Service;

use Symfony\Component\Console\Output\OutputInterface;
final class ConsoleDeniedService
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
