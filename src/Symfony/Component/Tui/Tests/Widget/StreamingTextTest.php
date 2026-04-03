<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Tests\Widget;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Ansi\AnsiUtils;
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Widget\StreamingTextWidget;

class StreamingTextTest extends TestCase
{
    public function testAppendTextAccumulates()
    {
        $widget = new StreamingTextWidget();
        $widget->appendText('Hello ');
        $widget->appendText('World');

        $this->assertSame('Hello World', $widget->getText());
    }

    public function testRenderWrapsToWidth()
    {
        $longText = 'This is a longer text that should wrap across multiple lines when rendered';
        $widget = new StreamingTextWidget($longText);
        $lines = $widget->render(new RenderContext(20, 24));

        $this->assertGreaterThan(1, \count($lines));

        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(20, AnsiUtils::visibleWidth($line));
        }
    }

    public function testFreezeStopsAppending()
    {
        $widget = new StreamingTextWidget('Hello');
        $widget->freeze();

        $this->assertTrue($widget->isFrozen());

        $widget->appendText(' World');
        $this->assertSame('Hello', $widget->getText());
    }

    public function testClearResetsState()
    {
        $widget = new StreamingTextWidget('Hello');
        $widget->freeze();

        $widget->clear();

        $this->assertSame('', $widget->getText());
        $this->assertFalse($widget->isFrozen());
    }

    public function testAnsiPreserved()
    {
        $styledText = "\x1b[32mGreen text\x1b[0m";
        $widget = new StreamingTextWidget($styledText);
        $lines = $widget->render(new RenderContext(80, 24));

        $this->assertCount(1, $lines);
        $this->assertStringContainsString("\x1b[32m", $lines[0]);
        $this->assertStringContainsString('Green text', $lines[0]);
    }

    public function testEmptyRender()
    {
        $widget = new StreamingTextWidget();
        $lines = $widget->render(new RenderContext(80, 24));

        $this->assertSame([], $lines);
    }

    public function testSetTextResetsFrozen()
    {
        $widget = new StreamingTextWidget('Hello');
        $widget->freeze();

        $this->assertTrue($widget->isFrozen());

        $widget->setText('New text');

        $this->assertFalse($widget->isFrozen());
        $this->assertSame('New text', $widget->getText());

        // Should be able to append again
        $widget->appendText(' more');
        $this->assertSame('New text more', $widget->getText());
    }
}
