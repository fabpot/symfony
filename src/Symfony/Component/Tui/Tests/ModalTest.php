<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\InputWidget;
use Symfony\Component\Tui\Widget\TextWidget;

class ModalTest extends TestCase
{
    public function testAddOverlayRendersWidget()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);

        $tui->add(new TextWidget('Main content'));
        $tui->start();
        $tui->processRender();

        $overlay = new TextWidget('Overlay dialog');
        $tui->addOverlay($overlay);
        $tui->processRender();

        $output = $terminal->getOutput();
        $this->assertStringContainsString('Main content', $output);
        $this->assertStringContainsString('Overlay dialog', $output);

        $tui->stop();
    }

    public function testRemoveOverlayRemovesWidget()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);

        $tui->add(new TextWidget('Main content'));
        $tui->start();

        $overlay = new TextWidget('Overlay dialog');
        $tui->addOverlay($overlay);
        $tui->processRender();
        $this->assertStringContainsString('Overlay dialog', $terminal->getOutput());

        $terminal->clearOutput();
        $tui->removeOverlay($overlay);
        $tui->processRender();

        $output = $terminal->getOutput();
        $this->assertStringContainsString('Main content', $output);
        $this->assertStringNotContainsString('Overlay dialog', $output);

        $tui->stop();
    }

    public function testOverlayContainerAutoCleanup()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);
        $tui->start();

        $overlay = new TextWidget('Temporary');
        $tui->addOverlay($overlay);

        // The overlay container should exist
        $found = $tui->getById('__tui_overlay');
        $this->assertNotNull($found);

        // Remove the overlay widget
        $tui->removeOverlay($overlay);

        // The overlay container should be removed from the root tree
        $this->expectException(\Symfony\Component\Tui\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('No widget found with id "__tui_overlay"');
        $tui->getById('__tui_overlay');
    }

    public function testAddOverlaySetsFocus()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);

        $mainInput = new InputWidget();
        $tui->add($mainInput);
        $tui->setFocus($mainInput);
        $this->assertSame($mainInput, $tui->getFocus());

        $tui->start();

        // Add a focusable overlay widget
        $overlayInput = new InputWidget();
        $tui->addOverlay($overlayInput);

        // Focus should move to the overlay widget
        $this->assertSame($overlayInput, $tui->getFocus());

        $tui->stop();
    }

    public function testRemoveOverlayIsNoOpWithoutOverlayContainer()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);
        $tui->start();

        // Removing an overlay when none were ever added should not throw
        $tui->removeOverlay(new TextWidget('Never added'));

        // Verify the TUI is still functional
        $this->assertTrue($tui->isRunning());

        $tui->stop();
    }

    public function testMultipleOverlayWidgets()
    {
        $terminal = new VirtualTerminal(40, 10);
        $tui = new Tui(terminal: $terminal);
        $tui->start();

        $overlay1 = new TextWidget('First overlay');
        $overlay2 = new TextWidget('Second overlay');
        $tui->addOverlay($overlay1);
        $tui->addOverlay($overlay2);
        $tui->processRender();

        $output = $terminal->getOutput();
        $this->assertStringContainsString('First overlay', $output);
        $this->assertStringContainsString('Second overlay', $output);

        // Removing one should keep the other
        $terminal->clearOutput();
        $tui->removeOverlay($overlay1);
        $tui->processRender();

        $output = $terminal->getOutput();
        $this->assertStringContainsString('Second overlay', $output);
        $this->assertStringNotContainsString('First overlay', $output);

        // Overlay container should still exist
        $found = $tui->getById('__tui_overlay');
        $this->assertNotNull($found);

        // Removing the last one should clean up the container
        $tui->removeOverlay($overlay2);

        $this->expectException(\Symfony\Component\Tui\Exception\InvalidArgumentException::class);
        $tui->getById('__tui_overlay');
    }
}
