<?php

namespace Symfony\Component\HttpKernel\View;

use Symfony\Component\HttpKernel\Controller\Response\Response;
use Symfony\Component\EventDispatcher\EventInterface;

class ControllerResponseListener
{
    public function handle(EventInterface $event)
    {
        $controllerValue = $event->get('controller_value');
        if (!$controllerValue instanceof Response) {
            return;
        }

        $event->setProcessed();
        $response = $event->get('response');
        $response->setContent($controllerValue->content);
        $response->setStatusCode($controllerValue->statusCode);

        foreach ($controllerValue->headers as $key => $value) {
            $response->headers->set($key, $value, true);
        }

        foreach ($controllerValue->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
    }
}