<?php

namespace Symfony\Component\HttpKernel\Security\ExceptionTranslation;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\SecurityContext;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    protected $errorPage;

    public function __construct($errorPage = null)
    {
        $this->errorPage = $errorPage;
    }

    public function handle(Event $event, Request $request, AccessDeniedException $exception)
    {
        if (null === $this->errorPage) {
            return;
        }

        $subRequest = Request::create($this->errorPage);
        $subRequest->attributes->set(SecurityContext::ACCESS_DENIED_ERROR, $exception->getMessage());

        $response = $event->getSubject()->handle($subRequest, HttpKernelInterface::SUB_REQUEST, true);
        $response->setStatusCode(403);

        return $response;
    }
}