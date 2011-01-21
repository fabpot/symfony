<?php

namespace Symfony\Component\HttpFoundation;

class ServerBag extends ParameterBag
{
    public function getHeaders()
    {
        $headers = array();

        foreach ($this->parameters as $key => $value) {
            if ('http_' === strtolower(substr($key, 0, 5))) {
                $headers[substr($key, 5)] = $value;
            }
        }

        return $headers;
    }
}
