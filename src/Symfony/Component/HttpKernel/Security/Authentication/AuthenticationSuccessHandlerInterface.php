<?php

namespace Symfony\Component\HttpKernel\Security\Authentication;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;

interface AuthenticationSuccessHandlerInterface
{
    function onAuthenticationSuccess(Event $event, Request $request, TokenInterface $token);
}