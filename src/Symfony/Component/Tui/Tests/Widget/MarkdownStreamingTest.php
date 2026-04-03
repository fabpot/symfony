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
use Symfony\Component\Tui\Render\RenderContext;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\MarkdownWidget;

class MarkdownStreamingTest extends TestCase
{
    public function testStreamingModeAppendText()
    {
        $md = $this->createMarkdown('');
        $md->setStreamingMode(true);

        $md->appendText('Hello ');
        $md->appendText('World');

        $this->assertSame('Hello World', $md->getText());
    }

    public function testNonStreamingAppendDelegatesToSetText()
    {
        $md = $this->createMarkdown('Hello');

        // Without streaming mode, appendText should concatenate via setText
        $md->appendText(' World');

        $this->assertSame('Hello World', $md->getText());
    }

    public function testPartialLineRendered()
    {
        $md = $this->createMarkdown('');
        $md->setStreamingMode(true);

        // Append text without a trailing newline (partial line)
        $md->appendText('Partial content');

        $lines = $md->render(new RenderContext(80, 24));

        $this->assertNotEmpty($lines);
        $content = implode("\n", $lines);
        $this->assertStringContainsString('Partial content', $content);
    }

    public function testSetStreamingModeFalseClearsState()
    {
        $md = $this->createMarkdown('');
        $md->setStreamingMode(true);

        $md->appendText("First line\n");
        $md->appendText('Partial');

        // Verify text accumulated
        $this->assertSame("First line\nPartial", $md->getText());

        // Disable streaming mode — should clear streaming state
        $md->setStreamingMode(false);

        // The text property should remain intact (setText was not called),
        // but internal streaming buffers are cleared
        $lines = $md->render(new RenderContext(80, 24));
        $content = implode("\n", $lines);
        $this->assertStringContainsString('First line', $content);
        $this->assertStringContainsString('Partial', $content);
    }

    public function testCompleteLinesParsedAsMarkdown()
    {
        $md = $this->createMarkdown('');
        $md->setStreamingMode(true);

        // Send a complete bold markdown line
        $md->appendText("This is **bold** text\n");

        $lines = $md->render(new RenderContext(80, 24));
        $content = implode("\n", $lines);

        // Should contain ANSI bold code from markdown parsing
        $this->assertStringContainsString("\x1b[1m", $content);
        $this->assertStringContainsString('bold', $content);
    }

    public function testStreamingAccumulatesAcrossChunks()
    {
        $md = $this->createMarkdown('');
        $md->setStreamingMode(true);

        $md->appendText('# He');
        $md->appendText("ading\n");
        $md->appendText('Some text');

        $this->assertSame("# Heading\nSome text", $md->getText());

        $lines = $md->render(new RenderContext(80, 24));
        $content = implode("\n", $lines);

        // The heading should be parsed (it's a complete line)
        $this->assertStringContainsString('Heading', $content);
        // The partial line should also appear
        $this->assertStringContainsString('Some text', $content);
    }

    /**
     * Create a MarkdownWidget attached to a Tui context so stylesheet
     * sub-element resolution (::bold, ::italic, etc.) works.
     */
    private function createMarkdown(string $text): MarkdownWidget
    {
        $terminal = new VirtualTerminal(80, 24);
        $tui = new Tui(terminal: $terminal);
        $md = new MarkdownWidget($text);
        $tui->add($md);

        return $md;
    }
}
