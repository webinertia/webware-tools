<?php

declare(strict_types=1);

namespace App\Fixtures\QueryHandler;

use Webware\MessageBus\QueryHandlerInterface;
use App\Fixtures\Repository\TargetAppRepository;
final class RepositoryPermittedHandler implements QueryHandlerInterface
{
    public function __construct(private TargetAppRepository $dependency)
    {
    }
}
