<?php

namespace DigiComp\FlowObjectResolving\Tests\Functional\Fixtures;

use DigiComp\FlowObjectResolving\ResolverTrait;
use Neos\Flow\Security\RequestPatternInterface;

class DefaultResolver
{
    use ResolverTrait;

    /**
     * @inheritDoc
     */
    protected static function getManagedInterface(): string
    {
        return RequestPatternInterface::class;
    }

    /**
     * @inheritDoc
     */
    protected static function getManagedNamespace(): string
    {
        return 'Security\\RequestPattern\\';
    }
}
