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
 * Tracks render revisions for dirty widgets.
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
trait DirtyWidgetTrait
{
    private int $renderRevision = 0;

    public function getRenderRevision(): int
    {
        return $this->renderRevision;
    }

    public function invalidate(): void
    {
        ++$this->renderRevision;
    }
}
