<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Retention implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 30, 'label' => __('1 month')],
            ['value' => 60, 'label' => __('2 months')],
            ['value' => 90, 'label' => __('3 months')],
            ['value' => 180, 'label' => __('6 months')],
            ['value' => 365, 'label' => __('1 year')],
            ['value' => 0, 'label' => __('Forever (never clean up)')],
        ];
    }
}
