<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\searchResultsInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Twint\Magento\Api\PairingHistoryRepositoryInterface;
use Twint\Magento\Model\ResourceModel\PairingHistory as ResourceModel;
use Twint\Magento\Model\ResourceModel\PairingHistory\Collection;
use Twint\Magento\Model\ResourceModel\PairingHistory\CollectionFactory;

class PairingHistoryRepository implements PairingHistoryRepositoryInterface
{
    public function __construct(
        private PairingHistoryFactory $factory,
        private readonly ResourceModel $resourceModel,
        private CollectionFactory $collectionFactory,
        private SearchResultsFactory $searchResultsFactory,
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
    public function save(PairingHistory $pairing)
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
}
