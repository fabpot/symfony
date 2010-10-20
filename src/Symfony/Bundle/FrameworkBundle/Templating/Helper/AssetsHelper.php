<?php

namespace Symfony\Bundle\FrameworkBundle\Templating\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\AssetsHelper as BaseAssetsHelper;
use Symfony\Bundle\TwigBundle\Templating\HelperInterface;
use Symfony\Bundle\TwigBundle\TokenParser\HelperTokenParser;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * AssetsHelper is the base class for all helper classes that manages assets.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class AssetsHelper extends BaseAssetsHelper implements HelperInterface
{
    /**
     * Constructor.
     *
     * @param Request      $request  A Request instance
     * @param string|array $baseURLs The domain URL or an array of domain URLs
     * @param string       $version  The version
     */
    public function __construct(Request $request, $baseURLs = array(), $version = null)
    {
        parent::__construct($request->getBasePath(), $baseURLs, $version);
    }

    /**
     * Creates a Twig token parser
     *
     * @return array of Twig_TokenParser instance that describe how to call this helper
     */
    public function getTwigTokenParsers()
    {
        return array(
            // {% asset 'css/blog.css' %}
            new HelperTokenParser('asset', '<location>', 'templating.helper.assets', 'getUrl'),
        );
    }
}
