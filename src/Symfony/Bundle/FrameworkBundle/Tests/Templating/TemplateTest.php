<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Templating;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Tests\Kernel;
use Symfony\Bundle\FrameworkBundle\Templating\Template;

class TemplateTest extends TestCase
{
    /**
     * @dataProvider getTemplateToPathProvider
     */
    public function testGetPathForTemplatesInABundle($template, $path)
    {
        if ($template->get('bundle')) {
            $this->assertEquals($template->getPath(), $path);
        }
    }

    /**
     * @dataProvider getTemplateToPathProvider
     */
    public function testGetPathForTemplatesOutOfABundle($template, $path)
    {
        if (!$template->get('bundle')) {
            $this->assertEquals($template->getPath('/root/path'), '/root/path'.$path);
        }        
    }

    public function getTemplateToPathProvider()
    {
        return array(
            array(new Template('FooBundle', 'Post', 'index', 'html', 'php'), '@FooBundle/Resources/views/Post/index.html.php'),
            array(new Template('FooBundle', '', 'index', 'html', 'twig'), '@FooBundle/Resources/views/index.html.twig'),
            array(new Template('', 'Post', 'index', 'html', 'php'), '/views/Post/index.html.php'),
            array(new Template('', '', 'index', 'html', 'php'), '/views/index.html.php'),
        );
    }
}
