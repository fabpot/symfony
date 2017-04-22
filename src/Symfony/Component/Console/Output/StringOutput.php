<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Output;

/**
 * StringOutput saves the output in a string variable.
 *
 * @author Christian Hammers <ch@lathspell.de>
 */
class StringOutput extends Output
{
    /** @var string */
    private $string = '';

    /**
     * Constructor.
     *
     * @param integer $verbosity The verbosity level (self::VERBOSITY_QUIET, self::VERBOSITY_NORMAL, self::VERBOSITY_VERBOSE)
     * @param Boolean $decorated Whether to decorate messages or not (null for auto-guessing)
     */
    public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null)
    {
        parent::__construct($verbosity, $decorated);
    }

    /**
     * Writes a message into the string buffer.
     *
     * @param string  $message A message to write to the output
     * @param Boolean $newline Whether to add a newline or not
     */
    public function doWrite($message, $newline) {
        $this->string .= $message . ($newline ? "\n" : '');
    }

    /**
     * Retrieve the output string.
     *
     * @return string
     */
    public function getString() {
        return $this->string;
    }
}
