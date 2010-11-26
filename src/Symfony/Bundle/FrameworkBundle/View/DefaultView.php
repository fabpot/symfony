<?php

namespace Symfony\Bundle\FrameworkBundle\View;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * DefaultView is a default view implementation
 *
 * Use it in controllers to build up a response in a format agnostic way
 * The View class takes care of encoding your data in json, xml, or renders a template for html.
 *
 * It is of course extensible and overrideable to provide a very flexible solution
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Lukas K. Smith <smith@pooteeweet.org>
 */
class DefaultView
{
    protected $container;
    protected $globalParameters;
    protected $customHandlers = array();

    protected $redirect;
    protected $template;
    protected $parameters;

    /**
     * Constructor
     *
     * Takes an optional array of global parameters, those will be merged with the
     * normal parameters before rendering an html template, which makes them useful for
     * data that you need to have accessible in the layout for every page on your site.
     *
     * @param ContainerInterface $container The service_container service.
     * @param array $globalParameters optional global array of parameters for html templates
     */
    public function __construct(ContainerInterface $container, array $globalParameters = array())
    {
        $this->container = $container;
        $this->globalParameters = $globalParameters;
    }

    /**
     * Registers a custom handler
     *
     * The handler must have the following signature: handler($viewObject, $request, $response)
     * It can use the public methods of this class to retrieve the needed data and return a
     * Response object ready to be sent.
     *
     * @param string $format the format that is handled
     * @param callback $callback handler callback
     */
    public function registerHandler($format, $callback)
    {
        $this->customHandlers[$format] = $callback;
    }

    /**
     * Sets a redirect using a route and parameters
     *
     * @param string $route route name
     * @param array $parameters route parameters
     * @param int $code optional http status code
     */
    public function setRouteRedirect($route, $parameters = array(), $code = 302)
    {
        $this->redirect = array(
            'location' => $this->container->get('router')->generate($route, $parameters),
            'statuscode' => $code,
        );
    }

    /**
     * Sets a redirect using an URI
     *
     * @param string $uri URI
     * @param int $code optional http status code
     */
    public function setUriRedirect($uri, $code = 302)
    {
        $this->redirect = array('location' => $uri, 'statuscode' => $code);
    }

    public function getRedirect()
    {
        return $this->redirect;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setGlobalParameters(array $globalParameters)
    {
        $this->globalParameters = $globalParameters;
    }

    public function getGlobalParameters()
    {
        return $this->globalParameters;
    }

    /**
     * Handles a request with the proper handler
     *
     * Decides on which handler to use based on the request format
     *
     * @param Request $request Request object
     * @param Response $response optional response object to use
     *
     * @param Response
     */
    public function handle(Request $request, Response $response = null)
    {
        $format = $request->getRequestFormat();

        if (null === $response) {
            $response = $this->container->get('response');
        }

        if (isset($this->customHandlers[$format])) {
            $callback = $this->customHandlers[$format];
            $response = call_user_func($callback, $this, $request, $response);
        } else {
            $method = 'handle'.ucfirst($format);
            if (!method_exists($this, $method)) {
                throw new NotFoundHttpException('Format '.$request->attributes->get('_format').' not supported, handler must be implemented');
            }
            $response = $this->$method($request, $response);
        }

        $this->reset();
        return $response;
    }

    /**
     * Resets the state of the view object
     */
    public function reset()
    {
        $this->redirect = null;
        $this->template = null;
        $this->parameters = null;
    }

    /**
     * Generic handler for default formats
     *
     * Handles redirects, or calls a "transform{$format}" method to transform the parameters into a response content
     *
     * @param Request $request
     * @param Response $response
     * @param string $format
     *
     * @return Response
     */
    protected function handleGeneric(Request $request, Response $response, $format)
    {
        if ($this->redirect) {
            $response->setRedirect($this->redirect['location'], $this->redirect['statuscode']);
            return $response;
        }

        $method = 'transform'.$format;
        if (!method_exists($this, $method)) {
            throw new NotFoundHttpException('Format '.$format.' not supported, transformer must be implemented');
        }

        $content = $this->$method($request, $response, $this->parameters);

        $response->setContent($content);
        return $response;
    }

    /**
     * Html format handler
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    protected function handleHtml(Request $request, Response $response)
    {
        return $this->handleGeneric($request, $response, 'html');
    }

    /**
     * Html parameter transformer
     *
     * Merges the global parameters with the given parameters and then
     * renders the given template
     *
     * @param Request $request
     * @param Response $response
     * @param array $parameters
     *
     * @return string
     */
    protected function transformHtml(Request $request, Response $response, $parameters)
    {
        $parameters = array_merge($this->globalParameters, $parameters);
        return $this->container->get('templating')->render($this->template, $parameters);
    }

    /**
     * Json format handler
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    protected function handleJson(Request $request, Response $response)
    {
        return $this->handleGeneric($request, $response, 'json');
    }

    /**
     * Json parameter transformer
     *
     * json encodes the parameters given to it, objects that do not implement
     * toArray() will only have their public properties present in the json
     *
     * @param Request $request
     * @param Response $response
     * @param array $parameters
     *
     * @return string
     */
    protected function transformJson(Request $request, Response $response, $parameters)
    {
        return json_encode($this->parametersToArray($parameters));
    }

    /**
     * Xml format handler
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    protected function handleXml(Request $request, Response $response)
    {
        return $this->handleGeneric($request, $response, 'xml');
    }

    /**
     * Xml parameter transformer
     *
     * converts the parameters given to it into an XML tree, objects that do not
     * implement toArray() or Traversable will be represented as empty nodes
     *
     * @param Request $request
     * @param Response $response
     * @param array $parameters
     *
     * @return string
     */
    protected function transformXml(Request $request, Response $response, $parameters)
    {
        if ($parameters instanceof \DOMDocument) {
            $dom = $parameters;
        } else if (is_string($parameters) && !empty($parameters)) {
            $dom = new \DOMDocument();
            $dom->loadXML($parameters);
        } else if (is_array($parameters)) {
            $dom = new \DOMDocument();
            $dom->loadXML("<response/>");
            $this->parametersToDom($parameters, $dom, $dom->documentElement);
        } else {
            throw new \UnexpectedValueException('Unsupported type '.gettype($parameters));
        }

        return $dom->saveXML();
    }

    /**
     * Recursively converts all objects in a parameter array into arrays
     *
     * Only objects implementing the toArray() method will be converted,
     * the others are left intact
     *
     * @param array $parameters
     *
     * @return array
     */
    public function parametersToArray(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $parameters[$key] = $this->parametersToArray($value->toArray());
            }
        }
        return $parameters;
    }

    /**
     * Converts an array to an XML DOM structure.
     *
     * @param mixed $data The array or traversable object to convert.
     * @param DOMDocument $domdoc The document into which to insert the
     *        result. The document must already have been created in memory.
     * @param DOMNode $domnode The element in $domdoc to which the result is added.
     * @param array $keys An array of key names, used internally for recursion
     * @param string $parentKey The key of the parent node, used internally for recursion
     */
    public function parametersToDom($data, $domdoc, $domnode, $keys = array(), $parentKey = null)
    {
        foreach ($data as $n => $node) {
            $v = $n;
            if (isset($keys[$parentKey])) {
                $key = $keys[$parentKey];
            } else {
                $key = 'entry';
            }

            $n = (is_numeric($n)) ? $key : $n;

            $elem = $domdoc->createElement($n);
            if ($elem instanceof \DOMNode) {
                if ($n === $key) {
                    $elem->setAttribute('key', $v);
                }

                if (is_array($node)) {
                    $this->parametersToDom($node, $domdoc, $elem, $keys, $v);
                } elseif (method_exists($node, 'toArray')) {
                    $this->parametersToDom($node->toArray(), $domdoc, $elem, $keys, $v);
                } elseif ($node instanceof \Traversable) {
                    $this->parametersToDom($node, $domdoc, $elem, $keys, $v);
                } else {
                    if ($node instanceof \DOMDocument){
                        $nodeObj = $domdoc->importNode($node->documentElement, true);
                    } elseif (is_bool($node) || is_null($node)) {
                        $node = strtolower(var_export($node, true));
                        $nodeObj = $domdoc->createTextNode($node);
                    } elseif (!is_object($node)) {
                        $nodeObj = $domdoc->createTextNode($node);
                    }

                    if (isset($nodeObj) && $nodeObj instanceof \DOMNode) {
                        $elem->appendChild($nodeObj);
                    }
                }

                if (null !== $domnode) {
                    $domnode->appendChild($elem);
                } else {
                    $domdoc->appendChild($elem);
                }
            }
        }
    }
}
