<?php

namespace DigiComp\FlowObjectResolving\Tests\Functional;

use DigiComp\FlowObjectResolving\Tests\Functional\Fixtures\DefaultResolver;
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
        $names = $resolver->getAvailableNames();
        $this->assertGreaterThanOrEqual(5, count($names));
        $this->assertContains('Neos.Flow:ControllerObjectName', $names);
        $this->assertContains('Neos.Flow:CsrfProtection', $names);
        $this->assertContains('Neos.Flow:Host', $names);
        $this->assertContains('Neos.Flow:Ip', $names);
        $this->assertContains('Neos.Flow:Uri', $names);
    }

    /**
     * @test
     */
    public function itResolvesByNameToARequestPattern()
    {
        $resolver = new DefaultResolver();
        $csrfProtection = $resolver->create('Neos.Flow:CsrfProtection', []);
        $this->assertInstanceOf(CsrfProtection::class, $csrfProtection);
    }
}
