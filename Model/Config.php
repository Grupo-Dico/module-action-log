<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_ENABLED = 'gdm_actionlog/general/enabled';
    private const XML_RETENTION = 'gdm_actionlog/general/retention_days';
    private const XML_LOG_VIEWS = 'gdm_actionlog/general/log_views';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_ENABLED);
    }

    /**
     * Also log page views and grid listings (high volume).
     */
    public function logViews(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_LOG_VIEWS);
    }

    /**
     * Days to keep records. 0 means keep forever.
     */
    public function getRetentionDays(): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_RETENTION));
    }
}
