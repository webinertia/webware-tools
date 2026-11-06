<?php

declare(strict_types=1);

namespace App\Fixtures\Validator;

use PhpDb\Adapter\Adapter;
final class PhpDbPermittedValidator
{
    public function __construct(private Adapter $dependency)
    {
    }
}
