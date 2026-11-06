<?php

declare(strict_types=1);

namespace PhpiidocTest\Fixtures;

use PhpDb\Validator\Validator;
final class ValidatorPermittedProbe
{
    public function __construct(private Validator $dependency)
    {
    }
}
