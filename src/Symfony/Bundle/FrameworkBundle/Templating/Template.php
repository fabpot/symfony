<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating;

use Symfony\Component\Templating\Template as BaseTemplate;

/**
 * Internal representation of a template.
 *
 * @author Victor Berchet <victor@suumit.com>
 */
class Template extends BaseTemplate
{
    public function __construct($bundle = null, $controller = null, $name = null, $format = null, $engine = null)
    {
        $this->parameters = array(
            'bundle'        => $bundle,
            'controller'    => $controller,
            'name'          => $name,
            'format'        => $format,
            'engine'        => $engine,
        );
    }

    /**
     * Returns the path to the template
     *  - as a path when the template is not part of a bundle
     *  - as a resource when the template is part of a bundle
     * 
     * @param string $root The template file root directory
     * @return string A path to the template or a resource
     */
    public function getPath($root = null)
    {
        $controller = $this->get('controller');
        $path = (empty($controller) ? '' : $controller.'/').$this->get('name').'.'.$this->get('format').'.'.$this->get('engine');

        return empty ($this->parameters['bundle']) ? $root.'/views/'.$path : '@'.$this->get('bundle').'/Resources/views/'.$path;
    }

}
