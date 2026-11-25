<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Block\Adminhtml\Log;

use GDMexico\AdminActionLog\Model\ResourceModel\ActionLog;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ResourceConnection;

class View extends Template
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var array|null|false
     */
    private $row = false;

    public function __construct(Context $context, ResourceConnection $resource, array $data = [])
    {
        parent::__construct($context, $data);
        $this->resource = $resource;
    }

    public function getRow(): ?array
    {
        if ($this->row === false) {
            $connection = $this->resource->getConnection();
            $row = $connection->fetchRow(
                $connection->select()
                    ->from($this->resource->getTableName(ActionLog::TABLE))
                    ->where('log_id = ?', (int)$this->getRequest()->getParam('id'))
            );
            $this->row = $row ?: null;
        }
        return $this->row;
    }

    /**
     * @return array list of ['type','id','label','action','changes' => [field => ['old','new']]]
     */
    public function getEntities(): array
    {
        $row = $this->getRow();
        if (!$row || empty($row['changes'])) {
            return [];
        }
        $decoded = json_decode((string)$row['changes'], true);
        return is_array($decoded) && isset($decoded['entities']) ? $decoded['entities'] : [];
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('gdm_actionlog/log/index');
    }
}
