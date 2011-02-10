<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Templating;

use Assetic\Factory\AssetFactory;
use Symfony\Component\Templating\Helper\Helper;

/**
 * The "assetic" templating helper.
 *
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 */
class AsseticHelper extends Helper
{
    protected $factory;
    protected $debug;

    public function __construct(AssetFactory $factory, $debug = false)
    {
        $this->factory = $factory;
        $this->debug = $debug;
    }

    /**
     * Gets the URLs for the configured asset.
     *
     * Usage looks something like this:
     *
     *     <?php foreach ($view['assetic']->urls(array('@jquery', 'js/src/core/*'), array('?yui_js'), 'js/core.js') as $url): ?>
     *         <script src="<?php echo $url ?>" type="text/javascript"></script>
     *     <?php endforeach; ?>
     *
     * When in debug mode, the helper returns an array of one or more URLs.
     * When not in debug mode it returns an array of one URL.
     *
     * @param array   $sourceUrls  An array of source URLs
     * @param array   $filterNames An array of filter names
     * @param string  $targetUrl   The target URL, pattern or extension
     * @param string  $assetName   The name to use for the asset in the asset manager
     * @param Boolean $debug       Force a debug mode
     *
     * @return array An array of URLs for the asset
     */
    public function urls(array $sourceUrls = array(), array $filterNames = array(), $targetUrl = null, $assetName = null, $debug = null)
    {
        if (null === $assetName) {
            $assetName = $this->factory->generateAssetName($sourceUrls, $filterNames);
        }

        if (null === $debug) {
            $debug = $this->debug;
        }

        $coll = $this->factory->createAsset($sourceUrls, $filterNames, $targetUrl, $assetName, $debug);

        if (!$debug) {
            return array($coll->getTargetUrl());
        }

        // create a pattern for each leaf's target url
        $pattern = $coll->getTargetUrl();
        if (false !== $pos = strrpos($pattern, '.')) {
            $pattern = substr($pattern, 0, $pos).'_*'.substr($pattern, $pos);
        } else {
            $pattern .= '_*';
        }

        $urls = array();
        foreach ($coll as $leaf) {
            $asset = $this->factory->createAsset(array($leaf->getSourceUrl()), $filterNames, $pattern, 'part'.(count($nodes) + 1), $debug);
            $urls[] = $asset->getTargetUrl();
        }

        return $urls;
    }

    public function getName()
    {
        return 'assetic';
    }
}
