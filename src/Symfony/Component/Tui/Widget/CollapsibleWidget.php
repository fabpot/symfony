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

use Symfony\Component\Tui\Ansi\AnsiUtils;
use Symfony\Component\Tui\Ansi\TextWrapper;
use Symfony\Component\Tui\Render\RenderContext;

/**
 * Collapsible content widget that shows a preview when collapsed.
 *
 * When collapsed, renders a configurable number of preview lines followed
 * by a hint showing how many lines are hidden. When expanded, renders
 * all content lines.
 *
 * Style elements:
 * - "header" — the header line
 * - "hint"   — the "+N more lines" indicator when collapsed
 *
 * @experimental
 */
class CollapsibleWidget extends AbstractWidget implements ToggleableInterface
{
    private bool $expanded = false;

    public function __construct(
        private string $content = '',
        private string $header = '',
        private int $previewLines = 3,
    ) {
    }

    public function setContent(string $content): static
    {
        if ($this->content !== $content) {
            $this->content = $content;
            $this->invalidate();
        }

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setHeader(string $header): static
    {
        if ($this->header !== $header) {
            $this->header = $header;
            $this->invalidate();
        }

        return $this;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setPreviewLines(int $lines): static
    {
        if ($this->previewLines !== $lines) {
            $this->previewLines = max(0, $lines);
            $this->invalidate();
        }

        return $this;
    }

    public function getPreviewLines(): int
    {
        return $this->previewLines;
    }

    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
        $this->invalidate();
    }

    public function setExpanded(bool $expanded): void
    {
        if ($this->expanded !== $expanded) {
            $this->expanded = $expanded;
            $this->invalidate();
        }
    }

    public function isExpanded(): bool
    {
        return $this->expanded;
    }

    public function render(RenderContext $context): array
    {
        $columns = $context->getColumns();

        if ('' === $this->content && '' === $this->header) {
            return [];
        }

        $result = [];

        // Render header
        if ('' !== $this->header) {
            $headerText = $this->applyElement('header', $this->header);
            $result[] = AnsiUtils::truncateToWidth($headerText, $columns, '');
        }

        // Normalize and split content into lines
        $normalized = str_replace("\t", '   ', $this->content);
        $contentLines = '' !== $normalized
            ? TextWrapper::wrapTextWithAnsi($normalized, $columns)
            : [];

        if ($this->expanded || \count($contentLines) <= $this->previewLines) {
            // Show all lines
            foreach ($contentLines as $line) {
                $result[] = AnsiUtils::truncateToWidth($line, $columns, '');
            }
        } else {
            // Show preview lines
            for ($i = 0; $i < $this->previewLines && $i < \count($contentLines); ++$i) {
                $result[] = AnsiUtils::truncateToWidth($contentLines[$i], $columns, '');
            }

            // Show hint
            $hiddenCount = \count($contentLines) - $this->previewLines;
            $hintText = \sprintf('▸ +%d more line%s', $hiddenCount, $hiddenCount > 1 ? 's' : '');
            $result[] = $this->applyElement('hint', $hintText);
        }

        return $result;
    }

    /**
     * Recursively toggle all ToggleableInterface widgets in a container tree.
     */
    public static function toggleAll(ContainerWidget $container): void
    {
        foreach ($container->all() as $child) {
            if ($child instanceof ToggleableInterface) {
                $child->toggle();
            }
            if ($child instanceof ContainerWidget) {
                self::toggleAll($child);
            }
        }
    }
}
