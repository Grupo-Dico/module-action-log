<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

use GDMexico\AdminActionLog\Model\ResourceModel\ActionLog;
use GDMexico\AdminActionLog\Model\Source\Result;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Writes records straight to the DB so that logging never triggers model events.
 */
class Logger
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(ResourceConnection $resource, RemoteAddress $remoteAddress)
    {
        $this->resource = $resource;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @param array $row keys: user_id, username, action_group, action, result, request_action, details, changes
     */
    public function log(array $row): void
    {
        $changes = $row['changes'] ?? null;
        $this->resource->getConnection()->insert(
            $this->resource->getTableName(ActionLog::TABLE),
            [
                'user_id' => $row['user_id'] ?? null,
                'username' => $this->cut($row['username'] ?? null, 64),
                'ip' => $this->cut((string)$this->remoteAddress->getRemoteAddress(), 45),
                'action_group' => $this->cut($row['action_group'] ?? null, 64),
                'action' => $this->cut($row['action'] ?? 'view', 32),
                'result' => $row['result'] ?? Result::SUCCESS,
                'request_action' => $this->cut($row['request_action'] ?? null, 255),
                'details' => $this->cut($row['details'] ?? null, 255),
                'changes' => $changes
                    ? json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
                    : null,
            ]
        );
    }

    private function cut(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return mb_substr($value, 0, $length);
    }
}
