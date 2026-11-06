<?php

declare(strict_types=1);

namespace MyTestApp\Fixtures;

use PhpDb\Validator\Validator;
final class ValidatorTrap
{
    public function __construct(private Validator $dependency)
    {
    }
}
