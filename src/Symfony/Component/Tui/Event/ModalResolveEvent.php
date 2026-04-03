<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Tui\Event;

use Symfony\Component\Tui\Widget\AbstractWidget;

/**
 * Dispatched by a modal widget to resolve a blocking modal() call with a typed result.
 *
 * @experimental
 */
class ModalResolveEvent extends AbstractEvent
{
    public function __construct(
        AbstractWidget $target,
        private readonly mixed $result,
    ) {
        parent::__construct($target);
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
