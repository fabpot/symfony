<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Console\Output;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StringOutput;

class StringOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testDoWriteAndGetOutput()
    {
        $output = new StringOutput();
        $output->writeln('foo');
        $output->writeln('bar');
        $this->assertEquals('foo'.PHP_EOL.'bar'.PHP_EOL, $output->getOutput(), '->getOutput() returns the current output');
    }

    public function testReset()
    {
        $output = new StringOutput();
        $output->writeln('foo');
        $this->assertEquals('foo'.PHP_EOL, $output->getOutput(), '->getOutput() returns the current output');
        $output->clear();
        $this->assertEquals('', $output->getOutput(), '->getOutput() is empty after clear');
    }
}
