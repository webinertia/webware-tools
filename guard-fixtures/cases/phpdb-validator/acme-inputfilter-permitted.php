<?php

declare(strict_types=1);

namespace Acme\Blog\InputFilter;

use PhpDb\Validator\Validator;
final class PermittedFilter
{
    public function __construct(private Validator $dependency)
    {
    }
}
