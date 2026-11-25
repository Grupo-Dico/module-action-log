<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Observer;

use GDMexico\AdminActionLog\Model\RequestLogger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ControllerPostdispatch implements ObserverInterface
{
    /**
     * @var RequestLogger
     */
    private $requestLogger;

    /**
     * @var LoggerInterface
     */
    private $psrLogger;

    public function __construct(RequestLogger $requestLogger, LoggerInterface $psrLogger)
    {
        $this->requestLogger = $requestLogger;
        $this->psrLogger = $psrLogger;
    }

    public function execute(Observer $observer)
    {
        try {
            $this->requestLogger->flush(false);
        } catch (\Throwable $e) {
            $this->psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
        }
    }
}
