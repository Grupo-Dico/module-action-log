<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Observer;

use GDMexico\AdminActionLog\Model\ChangeDetector;
use GDMexico\AdminActionLog\Model\RequestCollector;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Handles model_save_after and model_delete_after (adminhtml area only).
 * Changes are buffered and written as part of the admin action's single log row.
 */
class ModelChange implements ObserverInterface
{
    /**
     * @var ChangeDetector
     */
    private $detector;

    /**
     * @var RequestCollector
     */
    private $collector;

    /**
     * @var LoggerInterface
     */
    private $psrLogger;

    public function __construct(ChangeDetector $detector, RequestCollector $collector, LoggerInterface $psrLogger)
    {
        $this->detector = $detector;
        $this->collector = $collector;
        $this->psrLogger = $psrLogger;
    }

    public function execute(Observer $observer)
    {
        try {
            $model = $observer->getEvent()->getData('object');
            if (!$model instanceof AbstractModel) {
                return;
            }

            $isDelete = $observer->getEvent()->getName() === 'model_delete_after';
            $result = $this->detector->detect($model, $isDelete);
            if ($result === null) {
                return;
            }

            $this->collector->addEntity([
                'type' => get_class($model),
                'id' => $model->getId(),
                'label' => $result['label'],
                'action' => $result['action'],
                'changes' => $result['changes'],
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break an admin operation.
            $this->psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
        }
    }
}
