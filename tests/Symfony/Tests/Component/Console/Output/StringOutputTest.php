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
    protected $string;

    protected function setUp()
    {
    }

    public function testConstructor()
    {
        $output = new StringOutput(Output::VERBOSITY_QUIET, true);
        $this->assertEquals(Output::VERBOSITY_QUIET, $output->getVerbosity(), '__construct() takes the verbosity as its first argument');
        $this->assertTrue($output->isDecorated(), '__construct() takes the decorated flag as its second argument');
    }

    public function testDoWrite()
    {
        $output = new StringOutput();
        $output->writeln('foo');
        $this->assertEquals('foo'.PHP_EOL, $output->getString(), '->doWrite() writes to the string');
    }
}
