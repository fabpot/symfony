<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Controller;

use Symfony\Component\HttpKernel\Controller\Response\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * InternalController.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class InternalController extends ContainerAware
{
    /**
     * Forwards to the given controller with the given path.
     *
     * @param string $path       The path
     * @param string $controller The controller name
     */
    public function indexAction($path, $controller)
    {
        $request = $this->container->get('request');
        $attributes = $request->attributes;

        $attributes->remove('path');
        $attributes->remove('controller');
        if ('none' !== $path)
        {
            parse_str($path, $tmp);
            $attributes->add($tmp);
        }

        $subResponse = $this->container->get('http_kernel')->forward($controller, $attributes->all(), $request->query->all());

        return new Response($subResponse->getContent(), $subResponse->getStatusCode(), $subResponse->headers->all(), $subResponse->headers->getCookies());
    }
}
