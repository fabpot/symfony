<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Locale\Locale;

/**
 * NumberHelper displays culture information.
 *
 * @author Thomas Rabaix <thomas.rabaix@ekino.com>
 */
class NumberHelper extends Helper
{
    protected $session;

    protected $attributes = array();

    protected $textAttributes = array();
    
    /**
     * Constructor.
     *
     * @param Session $session A Session instance
     * @param array $attributes The default attributes to apply to the NumberFormatter instance
     * @param array $textAttributes The default text attributes to apply to the NumberFormatter instance
     */
    public function __construct(Session $session, array $attributes = array(), array $textAttributes = array())
    {
        $this->session          = $session;
        $this->attributes       = $attributes;
        $this->textAttributes   = $textAttributes;
    }

    /**
     * format a value with its percent representation (0.1 => 10%)
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatPercent($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::PERCENT, $attributes, $textAttributes, $locale);
    }

    /**
     * format a value with its duration representation
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatDuration($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::DURATION, $attributes, $textAttributes, $locale);
    }

    /**
     * format a value with its decimal representation
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatDecimal($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::DECIMAL, $attributes, $textAttributes, $locale);
    }

    /**
     * format a value with its spellout representation (1 => one)
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatSpellout($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::SPELLOUT, $attributes, $textAttributes, $locale);
    }

    /**
     * format a value with its currency representation (1 => one)
     *
     * @param float $number
     * @param $currency
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatCurrency($number, $currency, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        $formatter = $this->getFormatter($locale ?: $this->session->getLocale(), \NumberFormatter::CURRENCY, $attributes, $textAttributes);
        
        return $formatter->formatCurrency($number, $currency);
    }

    /**
     * format a value with its scientific representation (10 => 1E1)
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatScientific($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::SCIENTIFIC, $attributes, $textAttributes, $locale);
    }

     /**
     * format a value with its ordinal representation
     *
     * @param float $number
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function formatOrdinal($number, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {

        return $this->format($number, \NumberFormatter::ORDINAL, $attributes, $textAttributes, $locale);
    }

    /**
     * format a value with its provided style
     *
     * @param float $number
     * @param integer style
     * @param array $attributes
     * @param array $textAttributes
     * @param null $locale
     * @return string
     */
    public function format($number, $style, array $attributes = array(), array $textAttributes = array(), $locale = null)
    {
        $formatter = $this->getFormatter($locale ?: $this->session->getLocale(), $style, $attributes, $textAttributes);

        return $formatter->format($number);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'number';
    }

    protected function getFormatter($culture, $style, $attributes = array(), $textAttributes = array())
    {

        $formatter = new \NumberFormatter($culture, $style);

        foreach(array_merge($this->textAttributes, $textAttributes) as $name => $value) {
            $formatter->setTextAttribute($name, $value);
        }

        foreach(array_merge($this->attributes, $attributes) as $name => $value) {
            $formatter->setAttribute($name, $value);
        }

        return $formatter;
    }
}
