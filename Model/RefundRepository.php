<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\searchResultsInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Twint\Magento\Api\RefundRepositoryInterface;
use Twint\Magento\Model\ResourceModel\Refund as ResourceModel;
use Twint\Magento\Model\ResourceModel\Refund\Collection;
use Twint\Magento\Model\ResourceModel\Refund\CollectionFactory;
use Zend_Db_Expr;

class RefundRepository implements RefundRepositoryInterface
{
    public function __construct(
        private RefundFactory $factory,
        private readonly ResourceModel $resourceModel,
        private CollectionFactory $collectionFactory,
        private SearchResultsFactory $searchResultsFactory,
        private readonly ResourceConnection $resource,
        private ?CollectionProcessorInterface $collectionProcessor = null
    ) {
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
    public function save(Refund $entity)
    {
        try {
            $this->resourceModel->save($entity);
        } catch (ConnectionException $exception) {
            throw new CouldNotSaveException(__('Database connection error'), $exception, $exception->getCode());
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException(__('Unable to save item'), $e);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $this->getById($entity->getId());
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

    public function getTotalRefundedAmount(int $pairingId): float
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(ResourceModel::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, [
                'sum_amount' => new Zend_Db_Expr('SUM(amount)'),
            ])
            ->where('pairing_id = ?', $pairingId);

        return (float) $connection->fetchOne($select);
    }
}
