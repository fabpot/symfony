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
 * StringOutput collects all output for later retrieval.
 *
 *     $output = new StringOutput();
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class StringOutput extends Output
{
    protected $output = "";

    /**
     * Writes a message to the output.
     *
     * @param string $message A message to write to the output
     * @param Boolean $newline Whether to add a newline or not
     */
    public function doWrite($message, $newline)
    {
        $this->output .= $message.($newline ? PHP_EOL : '');
    }

    /**
     * Returns the collected output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Clears the collected output
     */
    public function clear()
    {
        $this->output = '';
    }
}
