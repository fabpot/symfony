<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Terminal;

use Symfony\Component\Tui\Ansi\AnsiUtils;

/**
 * A simple terminal emulator that interprets ANSI escape sequences
 * and maintains a screen buffer representing what the user actually sees.
 *
 * This is used in tests to convert raw terminal output (with differential
 * updates, cursor movements, etc.) into the actual rendered screen state.
 * It preserves ANSI styling (colors, bold, etc.) for accurate visual comparison.
 *
 * @experimental
 */
final class ScreenBuffer
{
    /**
     * @var array{
     *     bold: bool,
     *     dim: bool,
     *     italic: bool,
     *     underline: bool,
     *     blink: bool,
     *     reverse: bool,
     *     strikethrough: bool,
     *     fg: string|null,
     *     bg: string|null,
     *     underlineColor: string|null
     * }
     */
    private const DEFAULT_STYLE_STATE = [
        'bold' => false,
        'dim' => false,
        'italic' => false,
        'underline' => false,
        'blink' => false,
        'reverse' => false,
        'strikethrough' => false,
        'fg' => null,
        'bg' => null,
        'underlineColor' => null,
    ];

    /** @var array<int, array<int, array{char: string, style: string}>> */
    private array $cells = [];
    private int $cursorRow = 0;
    private int $cursorCol = 0;
    private int $width;
    private int $height;

    /**
     * Current style state - tracks individual attributes.
     *
     * @var array{
     *     bold: bool,
     *     dim: bool,
     *     italic: bool,
     *     underline: bool,
     *     blink: bool,
     *     reverse: bool,
     *     strikethrough: bool,
     *     fg: string|null,
     *     bg: string|null,
     *     underlineColor: string|null
     * }
     */
    private array $styleState = self::DEFAULT_STYLE_STATE;

    public function __construct(int $width = 80, int $height = 24)
    {
        $this->width = $width;
        $this->height = $height;
        $this->clear();
    }

    /**
     * Clear the screen buffer.
     */
    public function clear(): void
    {
        $this->cells = [];
        for ($row = 0; $row < $this->height; ++$row) {
            $this->cells[$row] = [];
        }
        $this->cursorRow = 0;
        $this->cursorCol = 0;
        $this->styleState = self::DEFAULT_STYLE_STATE;
    }

    /**
     * Process terminal output and update the screen buffer.
     */
    public function write(string $data): void
    {
        $i = 0;
        $len = \strlen($data);

        while ($i < $len) {
            $char = $data[$i];

            // Handle escape sequences
            if ("\x1b" === $char) {
                $consumed = $this->parseEscapeSequence($data, $i);
                $i += $consumed;
                continue;
            }

            // Handle special characters
            if ("\r" === $char) {
                $this->cursorCol = 0;
                ++$i;
                continue;
            }

            if ("\n" === $char) {
                ++$this->cursorRow;
                $this->cursorCol = 0; // Newline also resets column
                if ($this->cursorRow >= $this->height) {
                    $this->scrollUp();
                    $this->cursorRow = $this->height - 1;
                }
                ++$i;
                continue;
            }

            // Handle tab
            if ("\t" === $char) {
                $spaces = 8 - ($this->cursorCol % 8);
                for ($j = 0; $j < $spaces && $this->cursorCol < $this->width; ++$j) {
                    $this->putChar(' ');
                }
                ++$i;
                continue;
            }

            // Handle backspace (move cursor back)
            if ("\x08" === $char) {
                if ($this->cursorCol > 0) {
                    --$this->cursorCol;
                }
                ++$i;
                continue;
            }

            // Handle DEL (delete character at cursor, move cursor back)
            if ("\x7f" === $char) {
                if ($this->cursorCol > 0) {
                    --$this->cursorCol;
                    // Clear the character at the new cursor position
                    $this->cells[$this->cursorRow][$this->cursorCol] = ['char' => ' ', 'style' => ''];
                }
                ++$i;
                continue;
            }

            // Skip other control characters
            if (\ord($char) < 32 && "\x1b" !== $char) {
                ++$i;
                continue;
            }

            // Regular character - extract full grapheme cluster
            $grapheme = grapheme_extract($data, 1, \GRAPHEME_EXTR_COUNT, $i, $next);
            if (false !== $grapheme && '' !== $grapheme) {
                $this->putChar($grapheme);
                $i = $next;
            } else {
                ++$i;
            }
        }
    }

    /**
     * Get the current screen content as a string (without styles).
     */
    public function getScreen(): string
    {
        $result = [];
        $lastNonEmpty = -1;

        for ($row = 0; $row < $this->height; ++$row) {
            $line = $this->getLineText($row);
            $trimmed = rtrim($line);
            if ('' !== $trimmed) {
                $lastNonEmpty = $row;
            }
            $result[] = $trimmed;
        }

        // Only include lines up to the last non-empty line
        if ($lastNonEmpty >= 0) {
            $result = \array_slice($result, 0, $lastNonEmpty + 1);
        } else {
            $result = [];
        }

        return implode("\n", $result);
    }

    /**
     * Get the current screen content with ANSI styles preserved.
     */
    public function getStyledScreen(): string
    {
        $result = [];
        $lastNonEmpty = -1;

        for ($row = 0; $row < $this->height; ++$row) {
            $line = $this->getLineStyled($row);
            if (isset($this->cells[$row])) {
                foreach ($this->cells[$row] as $cell) {
                    if (' ' !== $cell['char'] && '' !== $cell['char']) {
                        $lastNonEmpty = $row;
                        break;
                    }
                }
            }
            $result[] = rtrim($line);
        }

        // Only include lines up to the last non-empty line
        if ($lastNonEmpty >= 0) {
            $result = \array_slice($result, 0, $lastNonEmpty + 1);
        } else {
            $result = [];
        }

        return implode("\n", $result);
    }

    /**
     * Get screen lines as array (without styles).
     *
     * @return string[]
     */
    public function getLines(): array
    {
        $lines = [];
        for ($row = 0; $row < $this->height; ++$row) {
            $lines[] = $this->getLineText($row);
        }

        return $lines;
    }

    /**
     * Get the cell data for external processing (e.g., HTML conversion).
     *
     * @return array<int, array<int, array{char: string, style: string}>>
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * Get the screen height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get a single line's text content.
     */
    private function getLineText(int $row): string
    {
        if (!isset($this->cells[$row]) || [] === $this->cells[$row]) {
            return '';
        }

        $line = '';
        $maxCol = max(array_keys($this->cells[$row]));

        for ($col = 0; $col <= $maxCol; ++$col) {
            $char = $this->cells[$row][$col]['char'] ?? ' ';
            // Skip wide character continuation cells (empty string placeholders)
            if ('' === $char) {
                continue;
            }
            $line .= $char;
        }

        return $line;
    }

    /**
     * Get a single line with ANSI styles.
     */
    private function getLineStyled(int $row): string
    {
        if (!isset($this->cells[$row]) || [] === $this->cells[$row]) {
            return '';
        }

        $line = '';
        $maxCol = max(array_keys($this->cells[$row]));

        $lastStyle = '';
        for ($col = 0; $col <= $maxCol; ++$col) {
            $cell = $this->cells[$row][$col] ?? ['char' => ' ', 'style' => ''];

            // Skip wide character continuation cells (empty string placeholders)
            if ('' === $cell['char']) {
                continue;
            }

            $style = $cell['style'];

            if ($style !== $lastStyle) {
                if ('' !== $lastStyle) {
                    $line .= "\x1b[0m"; // Reset before changing
                }
                if ('' !== $style) {
                    $line .= $style;
                }
                $lastStyle = $style;
            }

            $line .= $cell['char'];
        }

        if ('' !== $lastStyle) {
            $line .= "\x1b[0m";
        }

        return $line;
    }

    /**
     * Put a character at the current cursor position.
     */
    private function putChar(string $char): void
    {
        if ($this->cursorRow < 0 || $this->cursorRow >= $this->height) {
            return;
        }

        if (!isset($this->cells[$this->cursorRow])) {
            $this->cells[$this->cursorRow] = [];
        }

        // Fill any gaps with spaces
        for ($col = \count($this->cells[$this->cursorRow]); $col < $this->cursorCol; ++$col) {
            $this->cells[$this->cursorRow][$col] = ['char' => ' ', 'style' => ''];
        }

        $style = $this->buildStyleString();
        $charWidth = AnsiUtils::graphemeWidth($char);

        // If the wide character doesn't fit at the right edge, skip it
        if ($charWidth > 1 && $this->cursorCol + $charWidth > $this->width) {
            return;
        }

        $row = &$this->cells[$this->cursorRow];

        // Clean up wide character fragments in cells being overwritten
        for ($w = 0; $w < $charWidth; ++$w) {
            $col = $this->cursorCol + $w;
            if (isset($row[$col])) {
                if ('' === $row[$col]['char']) {
                    // This is a continuation cell, clear the wide char to its left
                    if ($col > 0 && isset($row[$col - 1]) && '' !== $row[$col - 1]['char'] && ' ' !== $row[$col - 1]['char']) {
                        $row[$col - 1] = ['char' => ' ', 'style' => ''];
                    }
                } elseif (' ' !== $row[$col]['char']) {
                    // This cell may be a wide char, clear its continuation cell to the right
                    if (isset($row[$col + 1]) && '' === $row[$col + 1]['char']) {
                        $row[$col + 1] = ['char' => ' ', 'style' => ''];
                    }
                }
            }
        }

        $row[$this->cursorCol] = [
            'char' => $char,
            'style' => $style,
        ];

        // For wide characters (e.g. CJK), mark continuation cell(s) as placeholders
        for ($w = 1; $w < $charWidth; ++$w) {
            $row[$this->cursorCol + $w] = [
                'char' => '',
                'style' => $style,
            ];
        }

        $this->cursorCol += $charWidth;
    }

    /**
     * Build an ANSI style string from the current style state.
     */
    private function buildStyleString(): string
    {
        $codes = [];

        if ($this->styleState['bold']) {
            $codes[] = '1';
        }
        if ($this->styleState['dim']) {
            $codes[] = '2';
        }
        if ($this->styleState['italic']) {
            $codes[] = '3';
        }
        if ($this->styleState['underline']) {
            $codes[] = '4';
        }
        if ($this->styleState['blink']) {
            $codes[] = '5';
        }
        if ($this->styleState['reverse']) {
            $codes[] = '7';
        }
        if ($this->styleState['strikethrough']) {
            $codes[] = '9';
        }
        if (null !== $this->styleState['fg']) {
            $codes[] = $this->styleState['fg'];
        }
        if (null !== $this->styleState['bg']) {
            $codes[] = $this->styleState['bg'];
        }
        if (null !== $this->styleState['underlineColor']) {
            $codes[] = $this->styleState['underlineColor'];
        }

        if ([] === $codes) {
            return '';
        }

        return "\x1b[".implode(';', $codes).'m';
    }

    /**
     * Scroll the screen up by one line.
     */
    private function scrollUp(): void
    {
        array_shift($this->cells);
        $this->cells[] = [];
    }

    /**
     * Parse an escape sequence and return the number of bytes consumed.
     */
    private function parseEscapeSequence(string $data, int $start): int
    {
        $len = \strlen($data);
        if ($start + 1 >= $len) {
            return 1;
        }

        $next = $data[$start + 1];
        $nextOrd = \ord($next);

        // CSI sequence: ESC [
        if ('[' === $next) {
            return $this->parseCsiSequence($data, $start);
        }

        // String sequences: OSC (ESC ]), DCS (ESC P), APC (ESC _), PM (ESC ^), SOS (ESC X)
        // All terminated by BEL (0x07) or ST (ESC \)
        if (']' === $next || 'P' === $next || '_' === $next || '^' === $next || 'X' === $next) {
            return $this->parseStringSequence($data, $start);
        }

        // nF announced sequences: ESC + intermediate bytes (0x20-0x2F)+ + final byte (0x30-0x7E)
        if ($nextOrd >= 0x20 && $nextOrd <= 0x2F) {
            $j = $start + 2;
            while ($j < $len && \ord($data[$j]) >= 0x20 && \ord($data[$j]) <= 0x2F) {
                ++$j;
            }
            if ($j < $len && \ord($data[$j]) >= 0x30 && \ord($data[$j]) <= 0x7E) {
                return $j + 1 - $start;
            }

            return $len - $start;
        }

        // Fe (0x40-0x5F), Fp (0x30-0x3F), Fs (0x60-0x7E) two-byte sequences
        if ($nextOrd >= 0x30 && $nextOrd <= 0x7E) {
            return 2;
        }

        // Unknown: skip the ESC byte
        return 1;
    }

    /**
     * Parse string sequence (OSC, DCS, APC, PM, SOS).
     * Format: ESC <introducer> ... ST (where ST is ESC \ or BEL).
     */
    private function parseStringSequence(string $data, int $start): int
    {
        $len = \strlen($data);
        $i = $start + 2; // Skip ESC + introducer byte

        // Find terminator: ST (ESC \) or BEL (\x07)
        while ($i < $len) {
            if ("\x07" === $data[$i]) {
                // BEL terminator
                return $i - $start + 1;
            }
            if ("\x1b" === $data[$i] && $i + 1 < $len && '\\' === $data[$i + 1]) {
                // ST terminator (ESC \)
                return $i - $start + 2;
            }
            ++$i;
        }

        // No terminator found - consume what we have
        return $len - $start;
    }

    /**
     * Parse CSI (Control Sequence Introducer) sequence.
     */
    private function parseCsiSequence(string $data, int $start): int
    {
        $len = \strlen($data);
        $i = $start + 2; // Skip ESC [

        // Collect parameter bytes (0x30-0x3F)
        $params = '';
        while ($i < $len && \ord($data[$i]) >= 0x30 && \ord($data[$i]) <= 0x3F) {
            $params .= $data[$i];
            ++$i;
        }

        // Collect intermediate bytes (0x20-0x2F)
        while ($i < $len && \ord($data[$i]) >= 0x20 && \ord($data[$i]) <= 0x2F) {
            ++$i;
        }

        // Final byte (0x40-0x7E)
        if ($i >= $len) {
            return $i - $start;
        }

        $finalByte = $data[$i];
        $consumed = $i - $start + 1;

        // Strip private mode prefix ("?") before parsing numeric parameters
        $paramStr = str_starts_with($params, '?') ? substr($params, 1) : $params;
        $nums = '' !== $paramStr ? array_map('intval', explode(';', $paramStr)) : [];

        switch ($finalByte) {
            case 'A': // Cursor Up
                $n = $nums[0] ?? 1;
                $this->cursorRow = max(0, $this->cursorRow - $n);
                break;

            case 'B': // Cursor Down
                $n = $nums[0] ?? 1;
                $this->cursorRow = min($this->height - 1, $this->cursorRow + $n);
                break;

            case 'C': // Cursor Forward
                $n = $nums[0] ?? 1;
                $this->cursorCol = min($this->width - 1, $this->cursorCol + $n);
                break;

            case 'D': // Cursor Back
                $n = $nums[0] ?? 1;
                $this->cursorCol = max(0, $this->cursorCol - $n);
                break;

            case 'G': // Cursor Horizontal Absolute
                $col = ($nums[0] ?? 1) - 1; // 1-indexed
                $this->cursorCol = max(0, min($this->width - 1, $col));
                break;

            case 'H': // Cursor Position
            case 'f':
                $row = ($nums[0] ?? 1) - 1;
                $col = ($nums[1] ?? 1) - 1;
                $this->cursorRow = max(0, min($this->height - 1, $row));
                $this->cursorCol = max(0, min($this->width - 1, $col));
                break;

            case 'J': // Erase in Display
                $mode = $nums[0] ?? 0;
                $this->eraseInDisplay($mode);
                break;

            case 'K': // Erase in Line
                $mode = $nums[0] ?? 0;
                $this->eraseInLine($mode);
                break;

            case 'm': // SGR (Select Graphic Rendition)
                $this->handleSgr($paramStr);
                break;

            case 'h': // Set Mode - ignore
            case 'l': // Reset Mode - ignore
                break;
        }

        return $consumed;
    }

    /**
     * Handle SGR (Select Graphic Rendition) - colors and styles.
     */
    private function handleSgr(string $params): void
    {
        if ('' === $params) {
            $params = '0';
        }

        $codes = array_map('intval', explode(';', $params));
        $i = 0;
        $codeCount = \count($codes);

        while ($i < $codeCount) {
            $code = $codes[$i];

            switch ($code) {
                case 0: // Reset all
                    $this->styleState = self::DEFAULT_STYLE_STATE;
                    break;

                case 1: // Bold
                    $this->styleState['bold'] = true;
                    break;
                case 2: // Dim
                    $this->styleState['dim'] = true;
                    break;
                case 3: // Italic
                    $this->styleState['italic'] = true;
                    break;
                case 4: // Underline
                    $this->styleState['underline'] = true;
                    break;
                case 5: // Blink
                    $this->styleState['blink'] = true;
                    break;
                case 7: // Reverse
                    $this->styleState['reverse'] = true;
                    break;
                case 9: // Strikethrough
                    $this->styleState['strikethrough'] = true;
                    break;

                    // Reset individual attributes
                case 22: // Reset bold and dim
                    $this->styleState['bold'] = false;
                    $this->styleState['dim'] = false;
                    break;
                case 23: // Reset italic
                    $this->styleState['italic'] = false;
                    break;
                case 24: // Reset underline
                    $this->styleState['underline'] = false;
                    break;
                case 25: // Reset blink
                    $this->styleState['blink'] = false;
                    break;
                case 27: // Reset reverse
                    $this->styleState['reverse'] = false;
                    break;
                case 29: // Reset strikethrough
                    $this->styleState['strikethrough'] = false;
                    break;

                    // Standard foreground colors (30-37)
                case 30: case 31: case 32: case 33: case 34: case 35: case 36: case 37:
                    $this->styleState['fg'] = (string) $code;
                    break;

                    // Default foreground color
                case 39:
                    $this->styleState['fg'] = null;
                    break;

                    // Standard background colors (40-47)
                case 40: case 41: case 42: case 43: case 44: case 45: case 46: case 47:
                    $this->styleState['bg'] = (string) $code;
                    break;

                    // Default background color
                case 49:
                    $this->styleState['bg'] = null;
                    break;

                    // Bright foreground colors (90-97)
                case 90: case 91: case 92: case 93: case 94: case 95: case 96: case 97:
                    $this->styleState['fg'] = (string) $code;
                    break;

                    // Bright background colors (100-107)
                case 100: case 101: case 102: case 103: case 104: case 105: case 106: case 107:
                    $this->styleState['bg'] = (string) $code;
                    break;

                    // 256-color and true-color foreground: 38;5;N or 38;2;R;G;B
                case 38:
                    if (isset($codes[$i + 1])) {
                        if (5 === $codes[$i + 1] && isset($codes[$i + 2])) {
                            // 256-color mode
                            $this->styleState['fg'] = '38;5;'.$codes[$i + 2];
                            $i += 2;
                        } elseif (2 === $codes[$i + 1] && isset($codes[$i + 2], $codes[$i + 3], $codes[$i + 4])) {
                            // True-color mode
                            $this->styleState['fg'] = '38;2;'.$codes[$i + 2].';'.$codes[$i + 3].';'.$codes[$i + 4];
                            $i += 4;
                        }
                    }
                    break;

                    // 256-color and true-color background: 48;5;N or 48;2;R;G;B
                case 48:
                    if (isset($codes[$i + 1])) {
                        if (5 === $codes[$i + 1] && isset($codes[$i + 2])) {
                            // 256-color mode
                            $this->styleState['bg'] = '48;5;'.$codes[$i + 2];
                            $i += 2;
                        } elseif (2 === $codes[$i + 1] && isset($codes[$i + 2], $codes[$i + 3], $codes[$i + 4])) {
                            // True-color mode
                            $this->styleState['bg'] = '48;2;'.$codes[$i + 2].';'.$codes[$i + 3].';'.$codes[$i + 4];
                            $i += 4;
                        }
                    }
                    break;

                    // 256-color and true-color underline color: 58;5;N or 58;2;R;G;B
                case 58:
                    if (isset($codes[$i + 1])) {
                        if (5 === $codes[$i + 1] && isset($codes[$i + 2])) {
                            // 256-color mode
                            $this->styleState['underlineColor'] = '58;5;'.$codes[$i + 2];
                            $i += 2;
                        } elseif (2 === $codes[$i + 1] && isset($codes[$i + 2], $codes[$i + 3], $codes[$i + 4])) {
                            // True-color mode
                            $this->styleState['underlineColor'] = '58;2;'.$codes[$i + 2].';'.$codes[$i + 3].';'.$codes[$i + 4];
                            $i += 4;
                        }
                    }
                    break;

                    // Default underline color
                case 59:
                    $this->styleState['underlineColor'] = null;
                    break;
            }

            ++$i;
        }
    }

    /**
     * Erase in display.
     */
    private function eraseInDisplay(int $mode): void
    {
        switch ($mode) {
            case 0: // Erase from cursor to end of screen
                $this->eraseInLine(0);
                for ($i = $this->cursorRow + 1; $i < $this->height; ++$i) {
                    $this->cells[$i] = [];
                }
                break;

            case 1: // Erase from start to cursor
                for ($i = 0; $i < $this->cursorRow; ++$i) {
                    $this->cells[$i] = [];
                }
                $this->eraseInLine(1);
                break;

            case 2: // Erase entire screen (but don't move cursor)
            case 3:
                for ($i = 0; $i < $this->height; ++$i) {
                    $this->cells[$i] = [];
                }
                break;
        }
    }

    /**
     * Erase in line.
     */
    private function eraseInLine(int $mode): void
    {
        if (!isset($this->cells[$this->cursorRow])) {
            $this->cells[$this->cursorRow] = [];

            return;
        }

        $row = &$this->cells[$this->cursorRow];

        switch ($mode) {
            case 0: // Erase from cursor to end of line
                // If cursor is on a continuation cell, also clear the wide char's main cell
                if (isset($row[$this->cursorCol]) && '' === $row[$this->cursorCol]['char']
                    && $this->cursorCol > 0 && isset($row[$this->cursorCol - 1])) {
                    unset($row[$this->cursorCol - 1]);
                }
                foreach ($row as $col => $cell) {
                    if ($col >= $this->cursorCol) {
                        unset($row[$col]);
                    }
                }
                break;

            case 1: // Erase from start of line to cursor
                foreach ($row as $col => $cell) {
                    if ($col <= $this->cursorCol) {
                        unset($row[$col]);
                    }
                }
                // If the last erased cell was a wide char's main cell, also clear its continuation
                if (isset($row[$this->cursorCol + 1]) && '' === $row[$this->cursorCol + 1]['char']) {
                    unset($row[$this->cursorCol + 1]);
                }
                break;

            case 2: // Erase entire line
                $row = [];
                break;
        }
    }
}
