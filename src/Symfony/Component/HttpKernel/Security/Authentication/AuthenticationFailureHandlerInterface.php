<?php

namespace Symfony\Component\HttpKernel\Security\Authentication;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

interface AuthenticationFailureHandlerInterface
{
    function onAuthenticationFailure(Event $event, Request $request, \Exception $exception);
}