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

use Symfony\Component\Tui\Widget\SettingsListWidget;

/**
 * Event dispatched when a setting value changes in SettingsList.
 *
 * @experimental
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SettingChangeEvent extends AbstractEvent
{
    private const ENABLED_VALUES = ['on', 'true', 'yes', '1', 'enabled'];
    private const DISABLED_VALUES = ['off', 'false', 'no', '0', 'disabled'];

    public function __construct(
        SettingsListWidget $target,
        private readonly string $id,
        private readonly string $value,
    ) {
        parent::__construct($target);
    }

    /**
     * Get the setting identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the new setting value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check if the value represents an enabled/truthy state.
     *
     * Matches: on, true, yes, 1, enabled
     */
    public function isEnabled(): bool
    {
        return \in_array(strtolower($this->value), self::ENABLED_VALUES, true);
    }

    /**
     * Check if the value represents a disabled/falsy state.
     *
     * Matches: off, false, no, 0, disabled
     */
    public function isDisabled(): bool
    {
        return \in_array(strtolower($this->value), self::DISABLED_VALUES, true);
    }
}
