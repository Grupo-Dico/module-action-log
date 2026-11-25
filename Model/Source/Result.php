<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Result implements OptionSourceInterface
{
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SUCCESS, 'label' => __('Success')],
            ['value' => self::FAILURE, 'label' => __('Failure')],
        ];
    }
}
