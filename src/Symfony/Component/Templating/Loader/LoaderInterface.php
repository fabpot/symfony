<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Templating\Loader;

use Symfony\Component\Templating\TemplateInterface;

/**
 * LoaderInterface is the interface all loaders must implement.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface LoaderInterface
{
    /**
     * Loads a template.
     *
     * @param TemplateInterface $template A template
     *
     * @return Storage|Boolean false if the template cannot be loaded, a Storage instance otherwise
     */
    function load(TemplateInterface $template);

    /**
     * Returns true if the template is still fresh.
     *
     * @param TemplateInterface     $template A template
     * @param integer               $time     The last modification time of the cached template (timestamp)
     */
    function isFresh(TemplateInterface $template, $time);
}
