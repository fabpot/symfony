<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\Extension;

/**
 * NumberExtension extends Twig with number capabilities.
 *
 * @author Thomas Rabaix <thomas.rabaix@ekino.com>
 */
class NumberExtension extends \Twig_Extension
{

    protected $helper;
    
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns the token parser instance to add to the existing list.
     *
     * @return array An array of Twig_TokenParser instances
     */
    public function getTokenParsers()
    {
        return array(
        );
    }

    public function getFunctions()
    {
        return array(
            'number_format_currency'    => new \Twig_Function_Method($this, 'formatCurrency', array('is_safe' => array('html'))),
            'number_format_decimal'     => new \Twig_Function_Method($this, 'formatDecimal', array('is_safe' => array('html'))),
            'number_format_scientific'  => new \Twig_Function_Method($this, 'formatScientific', array('is_safe' => array('html'))),
            'number_format_spellout'    => new \Twig_Function_Method($this, 'formatSpellout', array('is_safe' => array('html'))),
            'number_format_percent'     => new \Twig_Function_Method($this, 'formatPercent', array('is_safe' => array('html'))),
            'number_format_duration'    => new \Twig_Function_Method($this, 'formatDuration', array('is_safe' => array('html'))),
            'number_format_ordinal'     => new \Twig_Function_Method($this, 'formatOrdinal', array('is_safe' => array('html'))),
        );
    }

    public function formatCurrency($number, $currency, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatCurrency($number, $currency, $attributes, $textAttributes, $locale);
    }

    public function formatDecimal($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatCurrency($number, $attributes, $textAttributes, $locale);
    }

    public function formatScientific($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatScientific($number, $attributes, $textAttributes, $locale);
    }

    public function formatSpellout($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatSpellout($number, $attributes, $textAttributes, $locale);
    }

    public function formatPercent($number,  array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatPercent($number, $attributes, $textAttributes, $locale);
    }

    public function formatDuration($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatDuration($number, $attributes, $textAttributes, $locale);
    }

    public function formatOrdinal($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        return $this->helper->formatOrdinal($number, $attributes, $textAttributes, $locale);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'number';
    }
}
