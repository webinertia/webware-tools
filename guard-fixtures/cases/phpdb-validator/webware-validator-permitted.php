<?php

declare(strict_types=1);

namespace Webware\Fixtures\Validator;

use PhpDb\Validator\Validator;
final class PermittedValidator
{
    public function __construct(private Validator $dependency)
    {
    }
}
