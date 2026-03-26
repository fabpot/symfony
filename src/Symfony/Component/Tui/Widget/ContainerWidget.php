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

use Symfony\Component\Tui\Exception\LogicException;
use Symfony\Component\Tui\Render\RenderContext;

/**
 * Container widget that groups child widgets with optional styling.
 *
 * Supports:
 * - Vertical or horizontal layout (via Style::direction)
 * - Gap between children (via Style::gap)
 * - Padding, border, background via Style
 * - Vertically expandable children
 *
 * Layout direction and gap are style properties, configurable via
 * stylesheets or inline styles:
 *
 *     $container->setStyle(new Style(direction: Direction::Horizontal, gap: 1));
 *
 * Or via stylesheet rules:
 *
 *     $stylesheet->addRule('.panes', new Style(direction: Direction::Horizontal, gap: 2));
 *
 * Layout and chrome rendering is handled by the Renderer.
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ContainerWidget extends AbstractWidget implements ContainerInterface, VerticallyExpandableInterface
{
    /** @var AbstractWidget[] */
    private array $children = [];
    private bool $verticallyExpanded = false;

    public function __construct()
    {
    }

    /**
     * @return $this
     */
    public function add(AbstractWidget $widget): static
    {
        $widget->setParent($this);
        $this->children[] = $widget;
        $this->invalidate();

        if (null !== $this->getContext()) {
            $this->getContext()->attachChild($this, $widget);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function remove(AbstractWidget $widget): static
    {
        $index = array_search($widget, $this->children, true);
        if (false !== $index) {
            $child = $this->children[(int) $index];
            $child->setParent(null);
            if (null !== $this->getContext()) {
                $this->getContext()->detachChild($child);
            }
            array_splice($this->children, (int) $index, 1);
            $this->invalidate();
        }

        return $this;
    }

    /**
     * Remove all child widgets.
     *
     * @return $this
     */
    public function clear(): static
    {
        foreach ($this->children as $child) {
            $child->setParent(null);
            if (null !== $this->getContext()) {
                $this->getContext()->detachChild($child);
            }
        }
        if ([] !== $this->children) {
            $this->children = [];
            $this->invalidate();
        }

        return $this;
    }

    /**
     * Get all child widgets.
     *
     * @return AbstractWidget[]
     */
    public function all(): array
    {
        return $this->children;
    }

    /**
     * Set whether the container should fill available height.
     *
     * @return $this
     */
    public function expandVertically(bool $expand): self
    {
        if ($this->verticallyExpanded !== $expand) {
            $this->verticallyExpanded = $expand;
            $this->invalidate();
        }

        return $this;
    }

    /**
     * Check if the container should fill available height.
     *
     * Returns true if explicitly set, or if any child needs to expand vertically.
     * This allows vertical expansion to propagate up automatically from descendants.
     */
    public function isVerticallyExpanded(): bool
    {
        if ($this->verticallyExpanded) {
            return true;
        }

        // Check if any child needs fill height
        foreach ($this->all() as $child) {
            if ($child instanceof VerticallyExpandableInterface && $child->isVerticallyExpanded()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Not called in the standard rendering pipeline.
     *
     * The Renderer dispatches all ContainerWidget instances to
     * renderContainer(), which owns layout (direction, gap) and chrome
     * (padding, border, background). This method only exists to satisfy
     * the abstract contract from AbstractWidget.
     *
     * @return string[]
     */
    public function render(RenderContext $context): array
    {
        throw new LogicException(\sprintf('"%s" rendering is handled by the Renderer via "renderContainer()"; this method should never be called directly.', static::class));
    }
}
