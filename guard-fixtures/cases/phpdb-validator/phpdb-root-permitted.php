<?php

declare(strict_types=1);

namespace PhpDb\Fixtures;

use PhpDb\Validator\Validator;
final class ValidatorWiring
{
    public function __construct(private Validator $dependency)
    {
    }
}
