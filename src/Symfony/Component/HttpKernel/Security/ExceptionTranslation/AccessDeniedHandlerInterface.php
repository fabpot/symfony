<?php

namespace Symfony\Component\HttpKernel\Security\ExceptionTranslation;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Exception\AccessDeniedException;

/**
 * This is used by the ExceptionListener to translate an AccessDeniedException
 * to a Response object.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AccessDeniedHandlerInterface
{
    /**
     * Handles an access denied failure.
     *
     * @param Event $event
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     *
     * @return Response may return null
     */
    function handle(Event $event, Request $request, AccessDeniedException $accessDeniedException);
}