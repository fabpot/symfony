<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Render;

/**
 * A compositing layer: content lines at a position, optionally transparent.
 *
 * When transparent, cells with no explicit background preserve the
 * background from the layer below. Fully unstyled spaces are completely
 * transparent (the entire cell below shows through).
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Layer
{
    /**
     * @param string[] $lines       ANSI-formatted content lines
     * @param int      $row         Vertical offset in the composite
     * @param int      $col         Horizontal offset in the composite
     * @param bool     $transparent Whether cells with default background inherit from layers below
     * @param int|null $width       Explicit canvas width (used by the base layer to define the canvas size)
     * @param int|null $height      Explicit canvas height (used by the base layer to define the canvas size)
     */
    public function __construct(
        private readonly array $lines,
        private readonly int $row = 0,
        private readonly int $col = 0,
        private readonly bool $transparent = false,
        private readonly ?int $width = null,
        private readonly ?int $height = null,
    ) {
    }

    /**
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    public function isTransparent(): bool
    {
        return $this->transparent;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }
}
