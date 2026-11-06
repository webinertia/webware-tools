<?php

declare(strict_types=1);

namespace Acme\Blog\Service;

use PhpDb\Validator\Validator;
final class ValidatorDeniedService
{
    public function __construct(private Validator $dependency)
    {
    }
}
