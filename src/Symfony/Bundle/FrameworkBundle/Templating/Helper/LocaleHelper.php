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
 * LocaleHelper displays culture information.
 *
 * @author Thomas Rabaix <thomas.rabaix@ekino.com>
 */
class LocaleHelper extends Helper
{
    protected $session;

    /**
     * Constructor.
     *
     * @param HttpKernel $kernel A HttpKernel instance
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function country($code, $locale = null)
    {

        $countries = Locale::getDisplayCountries($locale ?: $this->session->getLocale());

        if (array_key_exists($code, $countries)) {
            return $countries[$code];
        }

        return '';
    }

    public function language($code, $locale = null)
    {

        $languages = Locale::getDisplayLanguages($locale ?: $this->session->getLocale());

        if (array_key_exists($code, $languages)) {
            return $languages[$code];
        }

        return '';
    }

    public function locale($code, $locale = null)
    {
        $locales = Locale::getDisplayLocales($locale ?: $this->session->getLocale());

        if (array_key_exists($code, $locales)) {
            return $locales[$code];
        }

        return '';
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'locale';
    }
}
