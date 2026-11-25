<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ActionLog extends AbstractDb
{
    public const TABLE = 'gdm_admin_action_log';

    protected function _construct()
    {
        $this->_init(self::TABLE, 'log_id');
    }
}
