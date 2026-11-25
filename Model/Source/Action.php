<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Action implements OptionSourceInterface
{
    public const VIEW = 'view';
    public const LISTING = 'listing';
    public const SAVE = 'save';
    public const DELETE = 'delete';
    public const MASS_ACTION = 'mass_action';
    public const FLUSH = 'flush';
    public const EXPORT = 'export';
    public const IMPORT = 'import';
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';

    /**
     * Actions that are always logged, even when "Log views and listings" is off.
     */
    public const MUTATING = [
        self::SAVE, self::DELETE, self::MASS_ACTION, self::FLUSH,
        self::EXPORT, self::IMPORT, self::LOGIN, self::LOGOUT,
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::VIEW, 'label' => __('View')],
            ['value' => self::LISTING, 'label' => __('Listing')],
            ['value' => self::SAVE, 'label' => __('Save')],
            ['value' => self::DELETE, 'label' => __('Delete')],
            ['value' => self::MASS_ACTION, 'label' => __('Mass Action')],
            ['value' => self::FLUSH, 'label' => __('Flush')],
            ['value' => self::EXPORT, 'label' => __('Export')],
            ['value' => self::IMPORT, 'label' => __('Import')],
            ['value' => self::LOGIN, 'label' => __('Login')],
            ['value' => self::LOGOUT, 'label' => __('Logout')],
        ];
    }
}
