<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Observer;

use GDMexico\AdminActionLog\Model\RequestCollector;
use GDMexico\AdminActionLog\Model\RequestLogger;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Remembers who is acting (the session user is gone after logout) and arms a shutdown fallback.
 */
class ControllerPredispatch implements ObserverInterface
{
    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var RequestCollector
     */
    private $collector;

    /**
     * @var RequestLogger
     */
    private $requestLogger;

    /**
     * @var LoggerInterface
     */
    private $psrLogger;

    public function __construct(
        Session $authSession,
        RequestCollector $collector,
        RequestLogger $requestLogger,
        LoggerInterface $psrLogger
    ) {
        $this->authSession = $authSession;
        $this->collector = $collector;
        $this->requestLogger = $requestLogger;
        $this->psrLogger = $psrLogger;
    }

    public function execute(Observer $observer)
    {
        try {
            $user = $this->authSession->getUser();
            if ($user && $user->getId()) {
                $this->collector->setUser((int)$user->getId(), (string)$user->getUserName());
            }

            if ($this->collector->registerShutdownOnce()) {
                $requestLogger = $this->requestLogger;
                $psrLogger = $this->psrLogger;
                register_shutdown_function(static function () use ($requestLogger, $psrLogger) {
                    try {
                        $requestLogger->flush(true);
                    } catch (\Throwable $e) {
                        $psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
                    }
                });
            }
        } catch (\Throwable $e) {
            $this->psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
        }
    }
}
