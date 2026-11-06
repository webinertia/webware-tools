<?php

declare(strict_types=1);

namespace Webware\Fixtures\Service;

use Webware\UserManager\UserManager;
final class UserManagerPermitted
{
    public function __construct(private UserManager $dependency)
    {
    }
}
