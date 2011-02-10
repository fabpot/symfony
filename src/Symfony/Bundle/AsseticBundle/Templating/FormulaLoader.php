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

use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\TemplateNameParser;

/**
 * Loads formulae from PHP templates.
 *
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 */
class FormulaLoader
{
    protected $parser;
    protected $loader;

    public function __construct(TemplateNameParser $parser, LoaderInterface $loader)
    {
        $this->parser = $parser;
        $this->loader = $loader;
    }

    public function load($templateName)
    {
        if (!$template = $this->loader->load($this->parser->parse($templateName))) {
            return array();
        }

        $tokens = token_get_all($template->getContent());

        // todo: find extract formulae from calls to $view['assetic']->urls()

        return array();
    }
}
