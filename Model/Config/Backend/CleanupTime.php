<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Converts the "hour,minute,second" time field into the cron expression read by etc/crontab.xml.
 */
class CleanupTime extends Value
{
    public const CRON_EXPR_PATH = 'gdm_actionlog/general/cron_expr';

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $valueFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->valueFactory = $valueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $time = explode(',', (string)$this->getValue());
        if (count($time) < 2 || !ctype_digit($time[0]) || !ctype_digit($time[1])
            || (int)$time[0] > 23 || (int)$time[1] > 59
        ) {
            throw new LocalizedException(__('Invalid cleanup time.'));
        }

        $expression = sprintf('%d %d * * *', (int)$time[1], (int)$time[0]);

        $this->valueFactory->create()
            ->load(self::CRON_EXPR_PATH, 'path')
            ->setValue($expression)
            ->setPath(self::CRON_EXPR_PATH)
            ->save();

        return parent::afterSave();
    }
}
