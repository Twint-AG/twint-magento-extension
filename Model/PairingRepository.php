<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\searchResultsInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\ResourceModel\Pairing as ResourceModel;
use Twint\Magento\Model\ResourceModel\Pairing\Collection;
use Twint\Magento\Model\ResourceModel\Pairing\CollectionFactory;
use Twint\Sdk\Value\TransactionStatus;
use Zend_Db_Expr;

class PairingRepository implements PairingRepositoryInterface
{
    public function __construct(
        private PairingFactory                 $factory,
        private readonly ResourceModel         $resourceModel,
        private CollectionFactory              $collectionFactory,
        private SearchResultsFactory           $searchResultsFactory,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly SortOrderBuilder      $sortOrderBuilder,
        private readonly FilterGroupBuilder    $filterGroupBuilder,
        private readonly FilterBuilder         $filterBuilder,
        private readonly ResourceConnection    $resource,
        private ?CollectionProcessorInterface  $collectionProcessor = null
    )
    {
        $this->collectionProcessor = $collectionProcessor ?: ObjectManager::getInstance()->get(
            CollectionProcessorInterface::class
        );
    }

    public function getById($id)
    {
        $entity = $this->factory->create();
        $this->resourceModel->load($entity, $id);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__("Requested item doesn't exist"));
        }

        return $entity;
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws AlreadyExistsException
     */
    public function save(Pairing $pairing): Pairing
    {
        try {
            $this->resourceModel->save($pairing);
        } catch (ConnectionException $exception) {
            throw new CouldNotSaveException(__('Database connection error'), $exception, $exception->getCode());
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException(__('Unable to save item'), $e);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $this->getById($pairing->getId());
    }

    public function getList(SearchCriteriaInterface $criteria): searchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var searchResultsInterface $results */
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setTotalCount($collection->getSize());
        $results->setItems($collection->getData());

        return $results;
    }

    public function getByPairingId(string $id): ?Pairing
    {
        $criteria = $this->criteriaBuilder->addFilter('pairing_id', $id)
            ->create();
        $items = $this->getList($criteria)
            ->getItems();

        if (!empty($items)) {
            $item = reset($items);

            $entity = $this->factory->create();
            $entity->setData($item);

            return $entity;
        }

        return null;
    }

    public function getByQuoteId(string $id): ?Pairing
    {
        $criteria = $this->criteriaBuilder->addFilter('quote_id', $id)
            ->create();
        $items = $this->getList($criteria)
            ->getItems();

        if (!empty($items)) {
            $item = reset($items);

            $entity = $this->factory->create();
            $entity->setData($item);

            return $entity;
        }

        return null;
    }

    public function getByOrderId(string $id): ?Pairing
    {
        $criteria = $this->criteriaBuilder->addFilter('order_id', $id)
            ->addSortOrder(
                $this->sortOrderBuilder
                    ->setField('id')
                    ->setDescendingDirection()
                    ->create()
            )
            ->create();
        $items = $this->getList($criteria)
            ->getItems();

        if (!empty($items)) {
            $item = reset($items);

            $entity = $this->factory->create();
            $entity->setData($item);

            return $entity;
        }

        return null;
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     */
    public function lock(Pairing $pairing)
    {
        $this->setLock($pairing->getId(), true);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     */
    public function unlock(Pairing $pairing)
    {
        $this->setLock($pairing->getId(), false);
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws AlreadyExistsException
     */
    private function setLock(string $id, bool $value)
    {
        $clone = $this->factory->create();
        $clone->setData([
            'id' => $id,
            'lock' => $value ? new Expression('timestamp(DATE_ADD(NOW(), INTERVAL 30 SECOND))') : null,
        ]);

        $this->save($clone);
    }

    public function getUnFinishes(): searchResultsInterface
    {
        $statuses = [
            TransactionStatus::ORDER_RECEIVED,
            TransactionStatus::ORDER_CONFIRMATION_PENDING,
            TransactionStatus::ORDER_PENDING,
        ];

        $statusFilter = $this->filterBuilder
            ->setField('transaction_status')
            ->setValue($statuses)
            ->setConditionType('in')
            ->create();

        // Create the first lock filter
        $lockFilter1 = $this->filterBuilder
            ->setField('lock')
            ->setValue(null)
            ->setConditionType('null')
            ->create();

        // Create the second lock filter
        $lockFilter2 = $this->filterBuilder
            ->setField('lock')
            ->setValue(new Zend_Db_Expr('now()'))
            ->setConditionType('lteq')
            ->create();

        // Create the first filter group for the lock filters (OR condition)
        $lockFilterGroup = $this->filterGroupBuilder->setFilters([$lockFilter1, $lockFilter2])->create();

        // Create the main filter group (AND condition)
        $mainFilterGroup = $this->filterGroupBuilder->setFilters([$statusFilter])->create();

        // Build the search criteria
        $criteria = $this->criteriaBuilder
            ->setFilterGroups([$mainFilterGroup, $lockFilterGroup])
            ->create();

        return $this->getList($criteria);
    }

    public function updateOrderId(string $orderId, int|string $quoteId): void
    {
        // Get the connection
        $connection = $this->resource->getConnection();

        // Define the table name
        $tableName = ResourceModel::TABLE_NAME;

        // Write the SQL update query
        $sql = "UPDATE $tableName SET order_id = :order_id WHERE quote_id = :quote_id";

        // Bind parameters
        $bind = [
            'order_id' => $orderId,
            'quote_id' => $quoteId
        ];

        // Execute the query
        $connection->query($sql, $bind);
    }
}
