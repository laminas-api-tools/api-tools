<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\ApiTools\MvcAuth\UnauthorizedListener;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class UnauthorizedListenerTest extends TestCase
{
    /**
     * @covers \Laminas\ApiTools\MvcAuth\UnauthorizedListener::__invoke
     */
    public function testInvokePropagates403ResponseWhenAuthenticationHasFailed(): void
    {
        $unauthorizedListener = new UnauthorizedListener();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse(new Response());

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, null, null);
        $mvcAuthEvent->setIsAuthorized(false);

        $invokeResponse = $unauthorizedListener->__invoke($mvcAuthEvent);
        $this->assertInstanceOf(ApiProblemResponse::class, $invokeResponse);
        $this->assertEquals(403, $invokeResponse->getStatusCode());
        $this->assertEquals('Forbidden', $invokeResponse->getReasonPhrase());
    }
}
