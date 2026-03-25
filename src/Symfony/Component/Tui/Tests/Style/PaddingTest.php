<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Tests\Style;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Tui\Exception\InvalidArgumentException;
use Symfony\Component\Tui\Style\Padding;

class PaddingTest extends TestCase
{
    /**
     * @param list<int> $input
     */
    #[DataProvider('fromArrayProvider')]
    public function testFromArray(array $input, int $top, int $right, int $bottom, int $left)
    {
        $padding = Padding::from($input);

        $this->assertSame($top, $padding->getTop());
        $this->assertSame($right, $padding->getRight());
        $this->assertSame($bottom, $padding->getBottom());
        $this->assertSame($left, $padding->getLeft());
    }

    /**
     * @return iterable<string, array{list<int>, int, int, int, int}>
     */
    public static function fromArrayProvider(): iterable
    {
        yield '1 element (all sides)' => [[3], 3, 3, 3, 3];
        yield '2 elements (y, x)' => [[1, 2], 1, 2, 1, 2];
        yield '3 elements (top, x, bottom)' => [[1, 2, 3], 1, 2, 3, 2];
        yield '4 elements (top, right, bottom, left)' => [[1, 2, 3, 4], 1, 2, 3, 4];
    }

    public function testAll()
    {
        $padding = Padding::all(7);

        $this->assertSame(7, $padding->getTop());
        $this->assertSame(7, $padding->getRight());
        $this->assertSame(7, $padding->getBottom());
        $this->assertSame(7, $padding->getLeft());
    }

    public function testXy()
    {
        $padding = Padding::xy(3, 1);

        $this->assertSame(1, $padding->getTop());
        $this->assertSame(3, $padding->getRight());
        $this->assertSame(1, $padding->getBottom());
        $this->assertSame(3, $padding->getLeft());
    }

    public function testXyDefaultY()
    {
        $padding = Padding::xy(5);

        $this->assertSame(0, $padding->getTop());
        $this->assertSame(5, $padding->getRight());
        $this->assertSame(0, $padding->getBottom());
        $this->assertSame(5, $padding->getLeft());
    }

    public function testNegativeValuesClampedToZero()
    {
        $padding = new Padding(-5, -3, -1, -2);

        $this->assertSame(0, $padding->getTop());
        $this->assertSame(0, $padding->getRight());
        $this->assertSame(0, $padding->getBottom());
        $this->assertSame(0, $padding->getLeft());
    }

    /**
     * @param list<int> $input
     */
    #[DataProvider('invalidFromArrayProvider')]
    public function testFromInvalidArray(array $input)
    {
        $this->expectException(InvalidArgumentException::class);
        Padding::from($input);
    }

    /**
     * @return iterable<string, array{list<int>}>
     */
    public static function invalidFromArrayProvider(): iterable
    {
        yield 'empty array' => [[]];
        yield '5 elements' => [[1, 2, 3, 4, 5]];
    }
}
