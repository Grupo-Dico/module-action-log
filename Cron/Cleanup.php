<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Cron;

use GDMexico\AdminActionLog\Model\Config;
use GDMexico\AdminActionLog\Model\ResourceModel\ActionLog;
use Magento\Framework\App\ResourceConnection;

/**
 * Deletes records older than the retention period configured in the admin.
 */
class Cleanup
{
    private const BATCH_SIZE = 10000;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(Config $config, ResourceConnection $resource)
    {
        $this->config = $config;
        $this->resource = $resource;
    }

    public function execute(): void
    {
        $days = $this->config->getRetentionDays();
        if ($days <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(ActionLog::TABLE);
        $sql = sprintf(
            'DELETE FROM %s WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) LIMIT %d',
            $connection->quoteIdentifier($table),
            self::BATCH_SIZE
        );

        do {
            $deleted = $connection->query($sql, ['days' => $days])->rowCount();
        } while ($deleted >= self::BATCH_SIZE);
    }
}
