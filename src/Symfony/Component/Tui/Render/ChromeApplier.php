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

use Symfony\Component\Tui\Ansi\AnsiUtils;
use Symfony\Component\Tui\Style\Style;
use Symfony\Component\Tui\Style\TextAlign;
use Symfony\Component\Tui\Widget\AbstractWidget;

/**
 * Applies chrome (padding, border, background) around widget content.
 *
 * Chrome is the visual frame around a widget's rendered lines:
 * padding adds space inside, borders draw a box, and background colors
 * fill the area. The result is cached for performance.
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class ChromeApplier
{
    private WidgetRendererInterface $widgetRenderer;

    public function setWidgetRenderer(WidgetRendererInterface $widgetRenderer): void
    {
        $this->widgetRenderer = $widgetRenderer;
    }

    /**
     * Apply chrome (padding, border, background) to rendered lines.
     *
     * @param string[] $lines
     *
     * @return string[]
     */
    public function apply(array $lines, int $width, Style $style, AbstractWidget $widget): array
    {
        $border = $style->getBorder();
        $padding = $style->getPadding();

        $borderLeft = null !== $border ? $border->getLeft() : 0;
        $borderRight = null !== $border ? $border->getRight() : 0;
        $borderTop = null !== $border ? $border->getTop() : 0;
        $borderBottom = null !== $border ? $border->getBottom() : 0;
        $paddingLeft = null !== $padding ? $padding->getLeft() : 0;
        $paddingRight = null !== $padding ? $padding->getRight() : 0;
        $paddingTop = null !== $padding ? $padding->getTop() : 0;
        $paddingBottom = null !== $padding ? $padding->getBottom() : 0;

        $hasVerticalPadding = 0 !== $paddingTop || 0 !== $paddingBottom;
        $hasHorizontalPadding = 0 !== $paddingLeft || 0 !== $paddingRight;
        $hasBorder = 0 !== $borderTop || 0 !== $borderBottom || 0 !== $borderLeft || 0 !== $borderRight;

        if (!$hasBorder && !$hasHorizontalPadding && !$hasVerticalPadding && $style->isPlain() && null === $style->getTextAlign()) {
            return $lines;
        }

        if ([] === $lines && !$hasVerticalPadding && 0 === $borderTop && 0 === $borderBottom) {
            return [];
        }

        $outerStyle = $this->resolveOuterStyle($widget);

        $innerWidth = max(1, $width - $borderLeft - $borderRight);

        // Clamp padding so it fits within the inner width, preserving
        // at least 1 column for content. Without this, excessive padding
        // (e.g. padding=50 in a 10-column container) would overflow.
        $maxHorizontalPadding = max(0, $innerWidth - 1);
        if ($paddingLeft + $paddingRight > $maxHorizontalPadding) {
            $paddingLeft = min($paddingLeft, $maxHorizontalPadding);
            $paddingRight = min($paddingRight, max(0, $maxHorizontalPadding - $paddingLeft));
        }

        $contentWidth = max(1, $innerWidth - $paddingLeft - $paddingRight);

        $processedLines = [];
        foreach ($lines as $line) {
            $processedLines[] = AnsiUtils::truncateToWidth($line, $contentWidth);
        }

        // If no content and no padding/border, return empty
        if ([] === $processedLines && 0 === $paddingTop && 0 === $paddingBottom
            && 0 === $borderTop && 0 === $borderBottom) {
            return [];
        }

        $styledEmptyLine = $style->apply(str_repeat(' ', $innerWidth));
        $topPadding = $paddingTop > 0 ? array_fill(0, $paddingTop, $styledEmptyLine) : [];
        $bottomPadding = $paddingBottom > 0 ? array_fill(0, $paddingBottom, $styledEmptyLine) : [];
        $textAlign = $style->getTextAlign() ?? TextAlign::Left;

        // For center/right alignment, compute offset from the widest line
        // so all lines shift uniformly (preserving internal alignment of
        // multi-line content like FIGlet).
        $alignPadLeft = 0;
        if (TextAlign::Left !== $textAlign) {
            $maxContentWidth = 0;
            foreach ($processedLines as $line) {
                $maxContentWidth = max($maxContentWidth, AnsiUtils::visibleWidth($line));
            }
            $availableSpace = max(0, $contentWidth - $maxContentWidth);
            $alignPadLeft = match ($textAlign) {
                TextAlign::Center => (int) floor($availableSpace / 2),
                TextAlign::Right => $availableSpace,
            };
        }

        $contentLines = [];
        foreach ($processedLines as $line) {
            $lineWithPad = str_repeat(' ', $paddingLeft + $alignPadLeft).$line;
            $visibleWidth = AnsiUtils::visibleWidth($lineWithPad);
            $rightPad = str_repeat(' ', max(0, $innerWidth - $visibleWidth));
            $contentLines[] = $style->apply($lineWithPad.$rightPad);
        }

        $innerLines = [...$topPadding, ...$contentLines, ...$bottomPadding];

        if (null !== $border) {
            $innerLines = $border->wrapLines(
                $innerLines,
                $innerWidth,
                $style,
                $outerStyle,
            );
        }

        return $innerLines;
    }

    /**
     * Compute inner dimensions (content area after border/padding).
     *
     * @return array{int, int} [innerColumns, innerRows]
     */
    public function computeInnerDimensions(int $columns, int $rows, Style $style): array
    {
        $border = $style->getBorder();
        $padding = $style->getPadding();

        $hChrome = (null !== $border ? $border->getLeft() + $border->getRight() : 0)
            + (null !== $padding ? $padding->getLeft() + $padding->getRight() : 0);
        $vChrome = (null !== $border ? $border->getTop() + $border->getBottom() : 0)
            + (null !== $padding ? $padding->getTop() + $padding->getBottom() : 0);

        return [
            max(1, $columns - $hChrome),
            max(1, $rows - $vChrome),
        ];
    }

    /**
     * Compute the top-left chrome offset (border + padding) for a style.
     *
     * @return array{int, int} [topOffset, leftOffset]
     */
    public function computeChromeOffset(Style $style): array
    {
        $border = $style->getBorder();
        $padding = $style->getPadding();

        $top = (null !== $border ? $border->getTop() : 0) + (null !== $padding ? $padding->getTop() : 0);
        $left = (null !== $border ? $border->getLeft() : 0) + (null !== $padding ? $padding->getLeft() : 0);

        return [$top, $left];
    }

    /**
     * Compute a RenderContext with inner dimensions (content area after border/padding).
     *
     * Widgets receive this context so they render into the content area without
     * needing to account for their own chrome.
     */
    public function computeInnerContext(RenderContext $context, Style $style): RenderContext
    {
        [$innerColumns, $innerRows] = $this->computeInnerDimensions($context->getColumns(), $context->getRows(), $style);

        // Strip layout properties from the style so leaf widgets only see
        // visual formatting (color, bold, etc.). The Renderer owns layout
        // (padding, border, gap, direction, hidden, cursorShape, textAlign, align, verticalAlign); widgets own content.
        return new RenderContext($innerColumns, $innerRows, $context->getStyle()->withoutLayoutProperties(), $context->getFontRegistry());
    }

    /**
     * Resolve the outer style for a widget by accumulating resolved
     * ancestor styles from root to immediate parent.
     *
     * This ensures that visual properties (color, background) set on
     * a grandparent propagate through intermediate containers that
     * don't override them.
     */
    private function resolveOuterStyle(AbstractWidget $widget): ?Style
    {
        // Collect ancestors from immediate parent to root
        $ancestors = [];
        $parent = $widget->getParent();
        while (null !== $parent) {
            $ancestors[] = $parent;
            $parent = $parent->getParent();
        }

        if ([] === $ancestors) {
            return null;
        }

        // Resolve each ancestor's style from root (last) to immediate
        // parent (first) so closer ancestors override more distant ones
        $resolvedStyles = [];
        for ($i = \count($ancestors) - 1; $i >= 0; --$i) {
            $resolvedStyles[] = $this->widgetRenderer->resolveStyle($ancestors[$i]);
        }

        return Style::mergeAll($resolvedStyles);
    }
}
