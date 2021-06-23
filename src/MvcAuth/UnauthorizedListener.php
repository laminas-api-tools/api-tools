<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;

class UnauthorizedListener
{
    /**
     * Determine if we have an authorization failure, and, if so, return a 403 response
     *
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if ($mvcAuthEvent->isAuthorized()) {
            return;
        }

        $response = new ApiProblemResponse(new ApiProblem(403, 'Forbidden'));
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $mvcEvent->setResponse($response);

        return $response;
    }
}
