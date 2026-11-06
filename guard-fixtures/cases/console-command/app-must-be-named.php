<?php

declare(strict_types=1);

namespace App\Fixtures\Console;

use Symfony\Component\Console\Attribute\AsCommand;
#[AsCommand(name: 'fixture')]
final class BadlyNamed
{
}
