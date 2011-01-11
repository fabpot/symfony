<?php

namespace Symfony\Bundle\FrameworkBundle\Templating;

use Symfony\Component\DependencyInjection\ContainerInterface;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * TemplateNameConverter converts template name from the short notation
 * "bundle:section:template(.format).renderer" to a template name
 * and an array of options.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class TemplateNameConverter
{
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The DI container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Merges the default options with the given set of options.
     *
     * @param array $options An array of options
     * @param array $defaults An array of default options
     *
     * @return array The merged set of options
     */
    protected function mergeDefaultOptions(array $options, array $defaults = array())
    {
        return array_replace(
            array(
                 'format' => '',
            ),
            $defaults,
            $options
        );
    }

    /**
     * Converts a short template notation to a template name and an array of options.
     *
     * @param string|array  $name     A short template template
     * @param array         $defaults An array of default options
     *
     * @return array An array composed of the template name and an array of options
     */
    public function fromShortNotation($name, array $defaults = array())
    {
        if (is_array($name)) {
            $options = $this->mergeDefaultOptions($name, $defaults);

            if (empty($name['name'])) {
                throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid.', var_export($name, true)));
            }

            return array($name['name'], $options);
        }

        $parts = explode(':', $name);
        if (3 !== count($parts)) {
            throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid.', $name));
        }

        $options = array(
            'bundle'     => str_replace('\\', '/', $parts[0]),
            'controller' => $parts[1],
        );
        $options = $this->mergeDefaultOptions($options, $defaults);

        $elements = explode('.', $parts[2]);
        if (3 === count($elements)) {
            $parts[2] = $elements[0];
            $options['format'] = '.'.$elements[1];
            $options['renderer'] = $elements[2];
        } elseif (2 === count($elements)) {
            $parts[2] = $elements[0];
            $options['renderer'] = $elements[1];
        } else {
            throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid.', $name));
        }

        return array($parts[2], $options);
    }
}
