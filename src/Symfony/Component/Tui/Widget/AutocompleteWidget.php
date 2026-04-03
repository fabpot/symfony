<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Widget;

use Symfony\Component\Tui\Event\CancelEvent;
use Symfony\Component\Tui\Event\ChangeEvent;
use Symfony\Component\Tui\Event\SelectEvent;
use Symfony\Component\Tui\Event\SubmitEvent;
use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Render\RenderContext;

/**
 * Input widget with autocomplete overlay.
 *
 * Composes an InputWidget for text editing and a SelectListWidget for
 * completion suggestions. The overlay appears when input matches the
 * trigger prefix and disappears on selection, cancellation, or when
 * no items match the filter.
 *
 * Keyboard behavior when overlay is visible:
 * - Up/Down: navigate suggestions
 * - Enter: select the highlighted suggestion
 * - Tab: fill input with highlighted value without selecting
 * - Escape: dismiss overlay
 *
 * @experimental
 */
class AutocompleteWidget extends AbstractWidget implements FocusableInterface, ParentInterface
{
    use FocusableTrait;
    use KeybindingsTrait;

    private InputWidget $input;
    private SelectListWidget $list;
    private bool $overlayVisible = false;

    /** @var array<array{value: string, label: string, description?: string}> */
    private array $items = [];

    private ?string $trigger = null;
    private ?\Closure $filterFn = null;

    /**
     * @param array<array{value: string, label: string, description?: string}> $items
     * @param string|null $trigger Prefix that activates the overlay (e.g. "/"). Null = always active.
     */
    public function __construct(
        array $items = [],
        ?string $trigger = null,
    ) {
        $this->items = $items;
        $this->trigger = $trigger;
        $this->input = new InputWidget();
        $this->list = new SelectListWidget($items);

        $this->wireInputEvents();
    }

    /**
     * @param array<array{value: string, label: string, description?: string}> $items
     */
    public function setItems(array $items): static
    {
        $this->items = $items;
        $this->invalidate();

        return $this;
    }

    public function setTrigger(?string $trigger): static
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * Set a custom filter function.
     *
     * The callable receives (array $item, string $query) and should return bool.
     * Default: prefix match on the item value.
     */
    public function setFilter(?callable $filter): static
    {
        $this->filterFn = null !== $filter ? $filter(...) : null;

        return $this;
    }

    public function setPrompt(string $prompt): static
    {
        $this->input->setPrompt($prompt);

        return $this;
    }

    public function setValue(string $value): static
    {
        $this->input->setValue($value);

        return $this;
    }

    public function getValue(): string
    {
        return $this->input->getValue();
    }

    public function getInputWidget(): InputWidget
    {
        return $this->input;
    }

    public function isOverlayVisible(): bool
    {
        return $this->overlayVisible;
    }

    // Event convenience methods

    public function onSelect(callable $callback): static
    {
        return $this->on(SelectEvent::class, $callback);
    }

    public function onSubmit(callable $callback): static
    {
        return $this->on(SubmitEvent::class, $callback);
    }

    public function onCancel(callable $callback): static
    {
        return $this->on(CancelEvent::class, $callback);
    }

    public function onChange(callable $callback): static
    {
        return $this->on(ChangeEvent::class, $callback);
    }

    public function handleInput(string $data): void
    {
        if (null !== $this->onInput && ($this->onInput)($data)) {
            return;
        }

        // When overlay is visible, intercept navigation keys
        if ($this->overlayVisible) {
            $kb = $this->getKeybindings();

            // Arrow keys navigate the list
            if ($kb->matches($data, 'overlay_up') || $kb->matches($data, 'overlay_down')) {
                $this->list->handleInput($data);
                $this->invalidate();

                return;
            }

            // Enter selects the highlighted item
            if ($kb->matches($data, 'overlay_select')) {
                $selected = $this->list->getSelectedItem();
                if (null !== $selected) {
                    $this->input->setValue('');
                    $this->hideOverlay();
                    $this->dispatch(new SelectEvent($this->list, $selected));

                    return;
                }
            }

            // Tab fills input with selected value
            if ($kb->matches($data, 'overlay_fill')) {
                $selected = $this->list->getSelectedItem();
                if (null !== $selected) {
                    $this->input->setValue($selected['value']);
                }
                $this->hideOverlay();

                return;
            }

            // Escape dismisses overlay
            if ($kb->matches($data, 'overlay_dismiss')) {
                $this->hideOverlay();

                return;
            }
        }

        // Delegate all other input to the inner InputWidget
        $this->input->handleInput($data);
    }

    /**
     * @return AbstractWidget[]
     */
    public function all(): array
    {
        return [$this->input, $this->list];
    }

    public function render(RenderContext $context): array
    {
        $ctx = $this->getContext();
        if (null === $ctx) {
            return [];
        }

        $lines = $ctx->renderWidget($this->input, $context);

        if ($this->overlayVisible) {
            $listLines = $ctx->renderWidget($this->list, $context);
            array_push($lines, ...$listLines);
        }

        return $lines;
    }

    /**
     * @return array<string, string[]>
     */
    protected static function getDefaultKeybindings(): array
    {
        return [
            'overlay_up' => [Key::UP],
            'overlay_down' => [Key::DOWN],
            'overlay_select' => [Key::ENTER],
            'overlay_fill' => [Key::TAB],
            'overlay_dismiss' => [Key::ESCAPE],
        ];
    }

    private function wireInputEvents(): void
    {
        // Track text changes to show/hide overlay
        $this->input->on(ChangeEvent::class, function (ChangeEvent $event) {
            $this->updateOverlayVisibility($event->getValue());
            $this->dispatch(new ChangeEvent($this, $event->getValue()));
        });

        // Re-dispatch submit/cancel from inner input
        $this->input->on(SubmitEvent::class, function (SubmitEvent $event) {
            if (!$this->overlayVisible) {
                $this->dispatch(new SubmitEvent($this, $event->getValue()));
            }
        });

        $this->input->on(CancelEvent::class, function () {
            if (!$this->overlayVisible) {
                $this->dispatch(new CancelEvent($this));
            }
        });
    }

    private function updateOverlayVisibility(string $value): void
    {
        if (!$this->shouldShowOverlay($value)) {
            $this->hideOverlay();

            return;
        }

        $filtered = $this->filterItems($value);
        if ([] !== $filtered) {
            $this->list->setItems($filtered);
            $this->showOverlay();
        } else {
            $this->hideOverlay();
        }
    }

    private function shouldShowOverlay(string $value): bool
    {
        if ('' === $value) {
            return false;
        }

        if (null === $this->trigger) {
            return true;
        }

        return str_starts_with($value, $this->trigger);
    }

    /**
     * @return array<array{value: string, label: string, description?: string}>
     */
    private function filterItems(string $value): array
    {
        $query = $value;
        if (null !== $this->trigger) {
            $query = substr($value, \strlen($this->trigger));
        }

        if (null !== $this->filterFn) {
            return array_values(array_filter(
                $this->items,
                fn (array $item) => ($this->filterFn)($item, $query),
            ));
        }

        // Default: prefix match on value (case-insensitive)
        $lower = strtolower($query);

        return array_values(array_filter(
            $this->items,
            static fn (array $item) => '' === $lower || str_starts_with(strtolower($item['value']), $lower),
        ));
    }

    private function showOverlay(): void
    {
        if (!$this->overlayVisible) {
            $this->overlayVisible = true;
            $this->invalidate();
        }
    }

    private function hideOverlay(): void
    {
        if ($this->overlayVisible) {
            $this->overlayVisible = false;
            $this->invalidate();
        }
    }
}
