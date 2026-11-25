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

class LoginSuccess implements ObserverInterface
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
            $user = $observer->getEvent()->getData('user');
            if (!$this->config->isEnabled() || !$user || !$user->getId()) {
                return;
            }
            $this->logger->log([
                'user_id' => (int)$user->getId(),
                'username' => (string)$user->getUserName(),
                'action_group' => (string)__('Admin Sign-in'),
                'action' => Action::LOGIN,
                'result' => Result::SUCCESS,
                'request_action' => 'adminhtml_auth_login',
            ]);
        } catch (\Throwable $e) {
            $this->psrLogger->error('GDMexico_AdminActionLog: ' . $e->getMessage());
        }
    }
}
