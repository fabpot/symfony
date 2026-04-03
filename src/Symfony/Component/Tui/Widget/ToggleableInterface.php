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

/**
 * Interface for widgets that can be expanded or collapsed.
 *
 * @experimental
 */
interface ToggleableInterface
{
    /**
     * Toggle between expanded and collapsed state.
     */
    public function toggle(): void;

    /**
     * Set the expanded state explicitly.
     */
    public function setExpanded(bool $expanded): void;

    /**
     * Whether the widget is currently expanded.
     */
    public function isExpanded(): bool;
}
