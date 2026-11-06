<?php

declare(strict_types=1);

namespace Webware\Acl\Fixtures;

use Webware\UserManager\UserManager;
final class UserManagerDenied
{
    public function __construct(private UserManager $dependency)
    {
    }
}
