<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Observer;

use GDMexico\AdminActionLog\Model\Config;
use GDMexico\AdminActionLog\Model\Logger;
use GDMexico\AdminActionLog\Model\Source\Action;
use GDMexico\AdminActionLog\Model\Source\Result;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class LoginFailed implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $psrLogger;

    public function __construct(Config $config, Logger $logger, LoggerInterface $psrLogger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->psrLogger = $psrLogger;
    }

    public function execute(Observer $observer)
    {
        try {
            if (!$this->config->isEnabled()) {
                return;
            }
            $this->logger->log([
                'user_id' => null,
                'username' => (string)$observer->getEvent()->getData('user_name'),
                'action_group' => (string)__('Admin Sign-in'),
                'action' => Action::LOGIN,
                'result' => Result::FAILURE,
                'request_action' => 'adminhtml_auth_login',
            ]);
        } catch (\Throwable $e) {
            $this->psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
        }
    }
}
