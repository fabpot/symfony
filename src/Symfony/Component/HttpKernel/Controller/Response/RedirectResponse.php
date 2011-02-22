<?php

namespace Symfony\Component\HttpKernel\Controller\Response;

class RedirectResponse extends Response
{
    public function __construct($url, $permanent = false)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        parent::__construct(
            sprintf('<html><head><meta http-equiv="refresh" content="1;url=%s"/></head></html>', htmlspecialchars($url, ENT_QUOTES)),
            $permanent ? 301 : 302,
            array('Location' => $url)
        );
    }
}