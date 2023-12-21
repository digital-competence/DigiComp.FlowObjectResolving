<?php

namespace DigiComp\FlowObjectResolving\Tests\Functional;

use DigiComp\FlowObjectResolving\Exception;
use DigiComp\FlowObjectResolving\Tests\Functional\Fixtures\DefaultResolver;
use DigiComp\FlowObjectResolving\UnknownObjectNameException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Security\RequestPattern\CsrfProtection;
use Neos\Flow\Tests\FunctionalTestCase;

class DefaultResolverTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function itListsAllRequestPatterns()
    {
        $resolver = new DefaultResolver();
        $resolver->registerObjectName(
            'DigiComp.FlowObjectResolving:CustomRequestPattern',
            'DigiComp.FlowObjectResolving:MyOwnCsrf'
        );
        $names = $resolver->getAvailableNames();
        $this->assertGreaterThanOrEqual(5, \count($names));
        $this->assertContains('Neos.Flow:ControllerObjectName', $names);
        $this->assertContains('Neos.Flow:CsrfProtection', $names);
        $this->assertContains('Neos.Flow:Host', $names);
        $this->assertContains('Neos.Flow:Ip', $names);
        $this->assertContains('Neos.Flow:Uri', $names);
        $this->assertContains('DigiComp.FlowObjectResolving:MyOwnCsrf', $names);
    }

    /**
     * @throws Exception
     * @throws UnknownPackageException
     * @test
     */
    public function itResolvesByNameToARequestPattern()
    {
        $resolver = new DefaultResolver();
        $objectName = $resolver->resolveObjectName('Neos.Flow:CsrfProtection');
        $csrfProtection = new $objectName();
        $this->assertInstanceOf(CsrfProtection::class, $csrfProtection);
    }

    /**
     * @test
     */
    public function itAllowsToHaveOwnNamesRegisteredAndResolved()
    {
        $resolver = new DefaultResolver();
        $resolver->registerObjectName(
            'DigiComp.FlowObjectResolving:MyOwnCsrf',
            'DigiComp.FlowObjectResolving:CustomRequestPattern'
        );
        $this->assertEquals(
            'DigiComp.FlowObjectResolving:CustomRequestPattern',
            $resolver->resolveObjectName('DigiComp.FlowObjectResolving:MyOwnCsrf')
        );
    }

    /**
     * @test
     */
    public function itDisallowsRegistrationsWhichAreUnknownToTheObjectManager(): void
    {
        $this->expectException(UnknownObjectNameException::class);
        $resolver = new DefaultResolver();
        $resolver->registerObjectName('myOne', 'AnObjectNameWhichDoesNotExist');
    }
}
