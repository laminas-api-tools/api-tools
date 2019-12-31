<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\ApiTools\MvcAuth\UnauthorizedListener;
use Laminas\Http\Header\WWWAuthenticate;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;

class UnauthorizedListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Laminas\ApiTools\MvcAuth\UnauthorizedListener::__invoke
     */
    public function testInvokePropagates403ResponseWhenAuthenticationHasFailed()
    {
        $unauthorizedListener = new UnauthorizedListener();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse(new Response);

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, null, null);
        $mvcAuthEvent->setIsAuthorized(false);

        $invokeResponse = $unauthorizedListener->__invoke($mvcAuthEvent);
        $this->assertInstanceOf('Laminas\ApiTools\ApiProblem\ApiProblemResponse', $invokeResponse);
        $this->assertEquals(403, $invokeResponse->getStatusCode());
        $this->assertEquals('Forbidden', $invokeResponse->getReasonPhrase());
    }

    /**
     * @covers Laminas\ApiTools\MvcAuth\UnauthorizedListener::__invoke
     */
    public function testInvokeWillPropagateMvcEventResponseHeaders()
    {
        $unauthorizedListener = new UnauthorizedListener();

        $mvcEvent = new MvcEvent();

        $mvcEventResponse = new Response();
        $mvcEventResponse->getHeaders()->addHeader(new WWWAuthenticate());
        $mvcEvent->setResponse($mvcEventResponse);

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, null, null);
        $mvcAuthEvent->setIsAuthorized(false);

        $invokeResponse = $unauthorizedListener->__invoke($mvcAuthEvent);
        $this->assertInstanceOf('Laminas\ApiTools\ApiProblem\ApiProblemResponse', $invokeResponse);

        $wwwAuthHeader = $invokeResponse->getHeaders()->get('WWW-Authenticate');
        $this->assertInstanceOf('Laminas\Http\Header\WWWAuthenticate', $wwwAuthHeader[0]);
    }
}
