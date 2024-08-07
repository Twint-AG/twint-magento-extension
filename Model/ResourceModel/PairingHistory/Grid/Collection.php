<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel\PairingHistory\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Twint\Magento\Model\ResourceModel\PairingHistory;
use Twint\Magento\Model\ResourceModel\RequestLog;

class Collection extends SearchResult
{
    /**
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        string $mainTable = PairingHistory::TABLE_NAME,
        string $resourceModel = PairingHistory::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

        $this->addOrder('parent_id', 'desc');
        $this->addOrder('created_at', 'desc');
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['request' => $this->getTable(RequestLog::TABLE_NAME)],
            'main_table.request_id = request.id',
            ['method', 'soap_action' ]
        );

        return $this;
    }
}
