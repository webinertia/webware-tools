<?php

declare(strict_types=1);

namespace Acme\Blog\Service;

use Symfony\Component\Console\Output\OutputInterface;
final class ConsoleDeniedService
{
    public function __construct(private OutputInterface $dependency)
    {
    }
}
