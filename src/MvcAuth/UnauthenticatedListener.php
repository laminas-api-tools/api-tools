<?php

namespace Laminas\ApiTools\MvcAuth;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;

class UnauthenticatedListener
{
    /**
     * Determine if we have an authentication failure, and, if so, return a 401 response
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if (! $mvcAuthEvent->hasAuthenticationResult()) {
            return;
        }

        $authResult = $mvcAuthEvent->getAuthenticationResult();
        if ($authResult->isValid()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = new ApiProblemResponse(new ApiProblem(401, 'Unauthorized'));
        $mvcEvent->setResponse($response);
        return $response;
    }
}
