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
use Symfony\Component\Tui\Render\Renderer;
use Symfony\Component\Tui\Widget\CollapsibleWidget;
use Symfony\Component\Tui\Widget\ContainerWidget;
use Symfony\Component\Tui\Widget\TextWidget;
use Symfony\Component\Tui\Widget\ToggleableInterface;

class CollapsibleTest extends TestCase
{
    public function testImplementsToggleableInterface()
    {
        $widget = new CollapsibleWidget();
        $this->assertInstanceOf(ToggleableInterface::class, $widget);
    }

    public function testCollapsedRenderShowsPreviewLines()
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5\nLine 6\nLine 7";
        $widget = new CollapsibleWidget($content, 'Header');

        $lines = $this->renderWidget($widget, 40, 24);

        // Header + 3 preview lines + hint = 5 lines
        $this->assertCount(5, $lines);
        $this->assertStringContainsString('Header', $lines[0]);
        $this->assertStringContainsString('Line 1', $lines[1]);
        $this->assertStringContainsString('Line 2', $lines[2]);
        $this->assertStringContainsString('Line 3', $lines[3]);
        $this->assertStringContainsString('+4 more lines', $lines[4]);
    }

    public function testExpandedRenderShowsAllLines()
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5";
        $widget = new CollapsibleWidget($content, 'Header');
        $widget->setExpanded(true);

        $lines = $this->renderWidget($widget, 40, 24);

        // Header + 5 content lines = 6 lines
        $this->assertCount(6, $lines);
        $this->assertStringContainsString('Header', $lines[0]);
        $this->assertStringContainsString('Line 5', $lines[5]);
    }

    public function testToggleFlipsState()
    {
        $widget = new CollapsibleWidget();
        $this->assertFalse($widget->isExpanded());

        $widget->toggle();
        $this->assertTrue($widget->isExpanded());

        $widget->toggle();
        $this->assertFalse($widget->isExpanded());
    }

    public function testSetExpandedIsIdempotent()
    {
        $widget = new CollapsibleWidget();

        $widget->setExpanded(false);
        $this->assertFalse($widget->isExpanded());

        $widget->setExpanded(true);
        $this->assertTrue($widget->isExpanded());

        $widget->setExpanded(true);
        $this->assertTrue($widget->isExpanded());
    }

    public function testHintShowsSingularWhenOneLine()
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4";
        $widget = new CollapsibleWidget($content, previewLines: 3);

        $lines = $this->renderWidget($widget, 40, 24);

        $lastLine = $lines[\count($lines) - 1];
        $this->assertStringContainsString('+1 more line', $lastLine);
        $this->assertStringNotContainsString('lines', $lastLine);
    }

    public function testHintShowsPluralWhenMultipleLines()
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5";
        $widget = new CollapsibleWidget($content, previewLines: 2);

        $lines = $this->renderWidget($widget, 40, 24);

        $lastLine = $lines[\count($lines) - 1];
        $this->assertStringContainsString('+3 more lines', $lastLine);
    }

    public function testNoHintWhenContentFitsInPreview()
    {
        $content = "Line 1\nLine 2";
        $widget = new CollapsibleWidget($content, previewLines: 3);

        $lines = $this->renderWidget($widget, 40, 24);

        // 2 content lines, no hint since they fit in preview
        $this->assertCount(2, $lines);
        foreach ($lines as $line) {
            $this->assertStringNotContainsString('more line', $line);
        }
    }

    public function testEmptyContentRendersEmpty()
    {
        $widget = new CollapsibleWidget();
        $lines = $widget->render(new RenderContext(40, 24));

        $this->assertSame([], $lines);
    }

    public function testHeaderOnlyRendersOneLine()
    {
        $widget = new CollapsibleWidget('', 'Just a header');
        $lines = $this->renderWidget($widget, 40, 24);

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Just a header', $lines[0]);
    }

    public function testLongLinesAreTruncated()
    {
        $longLine = str_repeat('A', 100);
        $widget = new CollapsibleWidget($longLine);
        $widget->setExpanded(true);

        $lines = $this->renderWidget($widget, 40, 24);

        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(40, AnsiUtils::visibleWidth($line));
        }
    }

    public function testAnsiContentPreserved()
    {
        $content = "\x1b[32mGreen text\x1b[0m\nNormal text";
        $widget = new CollapsibleWidget($content);
        $widget->setExpanded(true);

        $lines = $this->renderWidget($widget, 40, 24);

        $this->assertStringContainsString("\x1b[32m", $lines[0]);
        $this->assertStringContainsString('Green text', $lines[0]);
    }

    public function testCustomPreviewLines()
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4\nLine 5";
        $widget = new CollapsibleWidget($content, previewLines: 1);

        $lines = $this->renderWidget($widget, 40, 24);

        // 1 preview line + hint = 2 lines
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Line 1', $lines[0]);
        $this->assertStringContainsString('+4 more lines', $lines[1]);
    }

    public function testSetContentInvalidates()
    {
        $widget = new CollapsibleWidget('Old content');
        $widget->setExpanded(true);

        $lines1 = $this->renderWidget($widget, 40, 24);

        $widget->setContent('New content');
        $lines2 = $this->renderWidget($widget, 40, 24);

        $this->assertStringContainsString('Old content', $lines1[0]);
        $this->assertStringContainsString('New content', $lines2[0]);
    }

    public function testToggleAllRecursive()
    {
        $root = new ContainerWidget();

        $c1 = new CollapsibleWidget("Line 1\nLine 2\nLine 3\nLine 4\nLine 5");
        $c2 = new CollapsibleWidget("Line 1\nLine 2\nLine 3\nLine 4");

        $nested = new ContainerWidget();
        $c3 = new CollapsibleWidget("Line 1\nLine 2\nLine 3\nLine 4");
        $nested->add($c3);

        $root->add($c1);
        $root->add($c2);
        $root->add($nested);

        $this->assertFalse($c1->isExpanded());
        $this->assertFalse($c2->isExpanded());
        $this->assertFalse($c3->isExpanded());

        CollapsibleWidget::toggleAll($root);

        $this->assertTrue($c1->isExpanded());
        $this->assertTrue($c2->isExpanded());
        $this->assertTrue($c3->isExpanded());

        CollapsibleWidget::toggleAll($root);

        $this->assertFalse($c1->isExpanded());
        $this->assertFalse($c2->isExpanded());
        $this->assertFalse($c3->isExpanded());
    }

    public function testZeroPreviewLines()
    {
        $content = "Line 1\nLine 2\nLine 3";
        $widget = new CollapsibleWidget($content, previewLines: 0);

        $lines = $this->renderWidget($widget, 40, 24);

        // 0 preview lines + hint = 1 line
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('+3 more lines', $lines[0]);
    }

    /**
     * @return string[]
     */
    private function renderWidget(CollapsibleWidget $widget, int $columns, int $rows): array
    {
        $renderer = new Renderer();

        return $renderer->renderWidget($widget, new RenderContext($columns, $rows));
    }
}
