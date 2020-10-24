<?php

namespace DigiComp\FlowObjectResolving\Tests\Functional\Fixtures;

use DigiComp\FlowObjectResolving\ResolverTrait;
use Neos\Flow\Security\RequestPatternInterface;

class DefaultResolver
{
    use ResolverTrait;

    protected static function getManagedInterface(): string
    {
        return RequestPatternInterface::class;
    }

    protected static function getManagedNamespace(): string
    {
        return 'Security\\RequestPattern\\';
    }

    protected static function appendInterfaceName(): bool
    {
        return false;
    }
}
