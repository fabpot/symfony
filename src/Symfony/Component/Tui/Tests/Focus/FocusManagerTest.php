<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Tests\Focus;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Event\FocusEvent;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tests\KeySequenceParser;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\InputWidget;

class FocusManagerTest extends TestCase
{
    public function testFocusNavigation()
    {
        $tui = new Tui(terminal: new VirtualTerminal(80, 24));
        $focusManager = $tui->getFocusManager();

        $first = new InputWidget();
        $second = new InputWidget();

        $focusManager->add($first)->add($second);

        $tui->setFocus($first);
        $this->assertSame($first, $tui->getFocus());

        $focusManager->focusNext();
        $this->assertSame($second, $tui->getFocus());

        $focusManager->focusPrevious();
        $this->assertSame($first, $tui->getFocus());
    }

    public function testHandleInputMovesFocus()
    {
        $tui = new Tui(terminal: new VirtualTerminal(80, 24));
        $focusManager = $tui->getFocusManager();

        $first = new InputWidget();
        $second = new InputWidget();

        $focusManager->add($first)->add($second);
        $tui->setFocus($first);

        $tui->handleInput(KeySequenceParser::parseKeys('<F6>'));
        $this->assertSame($second, $tui->getFocus());

        $tui->handleInput(KeySequenceParser::parseKeys('<Shift+F6>'));
        $this->assertSame($first, $tui->getFocus());
    }

    public function testFocusChangedEventProvidesPreviousWidget()
    {
        $tui = new Tui(terminal: new VirtualTerminal(80, 24));
        $focusManager = $tui->getFocusManager();

        $first = new InputWidget();
        $second = new InputWidget();

        $focusManager->add($first)->add($second);

        $received = null;
        $tui->on(FocusEvent::class, static function (FocusEvent $event) use (&$received): void {
            $received = $event;
        });

        $tui->setFocus($first);
        $tui->setFocus($second);

        /* @var FocusEvent $received */
        $this->assertSame($second, $received->getTarget());
        $this->assertSame($first, $received->getPrevious());
    }
}
