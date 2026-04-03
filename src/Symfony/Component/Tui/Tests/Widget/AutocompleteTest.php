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
use Symfony\Component\Tui\Event\CancelEvent;
use Symfony\Component\Tui\Event\ChangeEvent;
use Symfony\Component\Tui\Event\SelectEvent;
use Symfony\Component\Tui\Event\SubmitEvent;
use Symfony\Component\Tui\Terminal\VirtualTerminal;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\AutocompleteWidget;
use Symfony\Component\Tui\Widget\FocusableInterface;
use Symfony\Component\Tui\Widget\InputWidget;
use Symfony\Component\Tui\Widget\ParentInterface;
use Symfony\Component\Tui\Widget\SelectListWidget;

class AutocompleteTest extends TestCase
{
    public function testImplementsInterfaces()
    {
        $widget = $this->createWidget();

        $this->assertInstanceOf(FocusableInterface::class, $widget);
        $this->assertInstanceOf(ParentInterface::class, $widget);
    }

    public function testAllReturnsInnerWidgets()
    {
        $widget = $this->createWidget();
        $children = $widget->all();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(InputWidget::class, $children[0]);
        $this->assertInstanceOf(SelectListWidget::class, $children[1]);
    }

    public function testSetValueDelegatesToInput()
    {
        $widget = $this->createWidget();

        $widget->setValue('hello');
        $this->assertSame('hello', $widget->getValue());

        $widget->setValue('');
        $this->assertSame('', $widget->getValue());
    }

    public function testGetInputWidgetReturnsInnerInput()
    {
        $widget = $this->createWidget();

        $this->assertInstanceOf(InputWidget::class, $widget->getInputWidget());
        $this->assertSame($widget->all()[0], $widget->getInputWidget());
    }

    public function testTypingUpdatesValue()
    {
        $widget = $this->createWidget();

        $widget->handleInput('h');
        $widget->handleInput('e');

        $this->assertSame('he', $widget->getValue());
    }

    public function testOverlayAppearsOnTyping()
    {
        [$widget] = $this->createWidgetWithTui();

        $this->assertFalse($widget->isOverlayVisible());

        // Type 'o' - matches 'opt1', 'opt2', 'opt3'
        $widget->handleInput('o');

        $this->assertTrue($widget->isOverlayVisible());
    }

    public function testEmptyInputHidesOverlay()
    {
        [$widget] = $this->createWidgetWithTui();

        // Type to show overlay
        $widget->handleInput('o');
        $this->assertTrue($widget->isOverlayVisible());

        // Backspace to empty
        $widget->handleInput("\x7f");
        $this->assertFalse($widget->isOverlayVisible());
    }

    public function testNoMatchesHidesOverlay()
    {
        [$widget] = $this->createWidgetWithTui();

        // Type something that matches nothing
        $widget->handleInput('z');
        $widget->handleInput('z');
        $widget->handleInput('z');

        $this->assertFalse($widget->isOverlayVisible());
    }

    public function testTriggerPrefix()
    {
        [$widget] = $this->createWidgetWithTui([
            ['value' => 'help', 'label' => 'Help'],
            ['value' => 'quit', 'label' => 'Quit'],
        ], '/');

        // Type without trigger - overlay should stay hidden
        $widget->handleInput('h');
        $this->assertFalse($widget->isOverlayVisible());

        // Clear and type with trigger
        $widget->handleInput("\x15"); // Ctrl+U (delete to line start)
        $widget->handleInput('/');
        $this->assertTrue($widget->isOverlayVisible());
    }

    public function testTriggerPrefixFiltersAfterTrigger()
    {
        [$widget] = $this->createWidgetWithTui([
            ['value' => 'help', 'label' => 'Help'],
            ['value' => 'quit', 'label' => 'Quit'],
        ], '/');

        // Type /q - should show overlay with 'quit' matching
        $widget->handleInput('/');
        $widget->handleInput('q');

        $this->assertTrue($widget->isOverlayVisible());

        // Type /qz - should hide, no match
        $widget->handleInput('z');

        $this->assertFalse($widget->isOverlayVisible());
    }

    public function testCustomFilter()
    {
        $items = [
            ['value' => 'apple', 'label' => 'Apple'],
            ['value' => 'banana', 'label' => 'Banana'],
            ['value' => 'cherry', 'label' => 'Cherry'],
        ];

        // Custom filter: substring match on label (not just prefix)
        [$widget] = $this->createWidgetWithTui($items, null, static fn (array $item, string $query) => '' === $query || str_contains(strtolower($item['label']), strtolower($query)));

        // Type 'an' - should match 'Banana' (contains 'an') via substring
        $widget->handleInput('a');
        $widget->handleInput('n');

        // With default prefix filter, 'an' wouldn't match 'banana' or 'cherry'
        // but 'apple' would match. With our substring filter, 'banana' also matches.
        $this->assertTrue($widget->isOverlayVisible());
    }

    public function testEscapeDismissesOverlay()
    {
        [$widget] = $this->createWidgetWithTui();

        $widget->handleInput('o');
        $this->assertTrue($widget->isOverlayVisible());

        // Press escape
        $widget->handleInput("\x1b");

        $this->assertFalse($widget->isOverlayVisible());
        // Value is preserved
        $this->assertSame('o', $widget->getValue());
    }

    public function testArrowKeysNavigateOverlay()
    {
        [$widget] = $this->createWidgetWithTui();

        $widget->handleInput('o');
        $this->assertTrue($widget->isOverlayVisible());

        // Down arrow - should navigate list, not modify input
        $widget->handleInput("\x1b[B");

        // Value should still be 'o' (not changed by list navigation)
        $this->assertSame('o', $widget->getValue());
    }

    public function testEnterSelectsItemFromOverlay()
    {
        [$widget, $tui] = $this->createWidgetWithTui();

        $selectedItem = null;
        $tui->on(SelectEvent::class, static function (SelectEvent $e) use (&$selectedItem) {
            $selectedItem = $e->getItem();
        });

        // Type to show overlay, then select
        $widget->handleInput('o');
        $this->assertTrue($widget->isOverlayVisible());

        // Press enter to select first item
        $widget->handleInput("\r");

        $this->assertNotNull($selectedItem);
        $this->assertSame('opt1', $selectedItem['value']);
        $this->assertFalse($widget->isOverlayVisible());
        // Input cleared after selection
        $this->assertSame('', $widget->getValue());
    }

    public function testTabFillsInputWithSelectedValue()
    {
        [$widget] = $this->createWidgetWithTui();

        $widget->handleInput('o');
        $this->assertTrue($widget->isOverlayVisible());

        // Press tab to fill
        $widget->handleInput("\t");

        $this->assertFalse($widget->isOverlayVisible());
        $this->assertSame('opt1', $widget->getValue());
    }

    public function testSubmitDelegatesWhenOverlayHidden()
    {
        [$widget, $tui] = $this->createWidgetWithTui();

        $submittedValue = null;
        $tui->on(SubmitEvent::class, static function (SubmitEvent $e) use (&$submittedValue) {
            $submittedValue = $e->getValue();
        });

        // Type something that doesn't match, so overlay is hidden
        $widget->handleInput('z');
        $widget->handleInput('z');
        $this->assertFalse($widget->isOverlayVisible());

        // Press enter - should submit
        $widget->handleInput("\r");

        $this->assertSame('zz', $submittedValue);
    }

    public function testCancelDelegatesWhenOverlayHidden()
    {
        [$widget, $tui] = $this->createWidgetWithTui();

        $cancelled = false;
        $tui->on(CancelEvent::class, static function () use (&$cancelled) {
            $cancelled = true;
        });

        // No overlay
        $this->assertFalse($widget->isOverlayVisible());

        // Escape should cancel
        $widget->handleInput("\x1b");

        $this->assertTrue($cancelled);
    }

    public function testChangePropagates()
    {
        [$widget, $tui] = $this->createWidgetWithTui();

        $changedValue = null;
        $tui->on(ChangeEvent::class, static function (ChangeEvent $e) use (&$changedValue) {
            $changedValue = $e->getValue();
        });

        $widget->handleInput('x');

        $this->assertSame('x', $changedValue);
    }

    public function testFocusable()
    {
        $widget = $this->createWidget();

        $this->assertFalse($widget->isFocused());

        $widget->setFocused(true);
        $this->assertTrue($widget->isFocused());

        $widget->setFocused(false);
        $this->assertFalse($widget->isFocused());
    }

    public function testOnInputCallbackConsumesEvent()
    {
        $widget = $this->createWidget();

        $intercepted = false;
        $widget->onInput(static function (string $data) use (&$intercepted): bool {
            if ('x' === $data) {
                $intercepted = true;

                return true;
            }

            return false;
        });

        $widget->handleInput('x');
        $this->assertTrue($intercepted);
        $this->assertSame('', $widget->getValue(), 'Consumed input should not be typed');
    }

    public function testSetItemsUpdatesCompletions()
    {
        [$widget] = $this->createWidgetWithTui();

        // Type 'n' - no match in original items
        $widget->handleInput('n');
        $this->assertFalse($widget->isOverlayVisible());

        // Update items with a matching one
        $widget->setItems([
            ['value' => 'new', 'label' => 'New Item'],
        ]);

        // Type another character to trigger re-evaluation
        $widget->handleInput('e');

        $this->assertTrue($widget->isOverlayVisible());
    }

    /**
     * @return AutocompleteWidget
     */
    private function createWidget(): AutocompleteWidget
    {
        return new AutocompleteWidget($this->getTestItems());
    }

    /**
     * @param array<array{value: string, label: string, description?: string}>|null $items
     *
     * @return array{AutocompleteWidget, Tui}
     */
    private function createWidgetWithTui(?array $items = null, ?string $trigger = null, ?callable $filter = null): array
    {
        $terminal = new VirtualTerminal(80, 24);
        $tui = new Tui(terminal: $terminal);
        $widget = new AutocompleteWidget($items ?? $this->getTestItems(), $trigger);

        if (null !== $filter) {
            $widget->setFilter($filter);
        }

        $tui->add($widget);

        return [$widget, $tui];
    }

    /**
     * @return array<array{value: string, label: string, description?: string}>
     */
    private function getTestItems(): array
    {
        return [
            ['value' => 'opt1', 'label' => 'Option 1', 'description' => 'First option'],
            ['value' => 'opt2', 'label' => 'Option 2', 'description' => 'Second option'],
            ['value' => 'opt3', 'label' => 'Option 3', 'description' => 'Third option'],
        ];
    }
}
