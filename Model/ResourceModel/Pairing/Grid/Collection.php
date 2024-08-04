<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel\Pairing\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Twint\Magento\Model\ResourceModel\Pairing;
use Twint\Magento\Model\ResourceModel\PairingHistory;

class Collection extends SearchResult
{
    /**
     * Initialize dependencies.
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
}
