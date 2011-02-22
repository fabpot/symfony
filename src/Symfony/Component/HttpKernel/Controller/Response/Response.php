<?php

namespace Symfony\Component\HttpKernel\Controller\Response;

class Response
{
    public $content;
    public $statusCode;
    public $headers;
    public $cookies;

    public function __construct($content = '', $statusCode = 200, $headers = array(), $cookies = array())
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->cookies = $cookies;
    }
}