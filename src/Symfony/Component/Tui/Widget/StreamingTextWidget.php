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

use Symfony\Component\Tui\Ansi\TextWrapper;
use Symfony\Component\Tui\Render\RenderContext;

/**
 * Widget optimized for streaming text content.
 *
 * Designed for use cases where text arrives incrementally (e.g., LLM output
 * streaming). Supports appendText() for efficient incremental updates and
 * freeze() to finalize the content.
 *
 * @experimental
 */
class StreamingTextWidget extends AbstractWidget
{
    private string $text = '';
    private bool $frozen = false;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    /**
     * Append a chunk of text to the current content.
     *
     * No-op if the widget is frozen.
     */
    public function appendText(string $chunk): static
    {
        if ($this->frozen) {
            return $this;
        }

        $this->text .= $chunk;
        $this->invalidate();
        $this->getContext()?->requestRender();

        return $this;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        $this->frozen = false;
        $this->invalidate();

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Clear all content and reset frozen state.
     */
    public function clear(): static
    {
        $this->text = '';
        $this->frozen = false;
        $this->invalidate();

        return $this;
    }

    /**
     * Freeze the widget, preventing further appendText() calls.
     */
    public function freeze(): static
    {
        $this->frozen = true;

        return $this;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function render(RenderContext $context): array
    {
        if ('' === $this->text) {
            return [];
        }

        $normalized = str_replace("\t", '   ', $this->text);

        return TextWrapper::wrapTextWithAnsi($normalized, $context->getColumns());
    }
}
