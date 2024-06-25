<?php

namespace Twint\Magento\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\searchResultsInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\ResourceModel\Pairing as ResourceModel;
use Twint\Magento\Model\ResourceModel\Pairing\CollectionFactory;
use Twint\Magento\Model\ResourceModel\Pairing\Collection;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class PairingRepository implements PairingRepositoryInterface
{
    public function __construct(
        private PairingFactory                 $factory,
        private readonly ResourceModel         $resourceModel,
        private CollectionFactory              $collectionFactory,
        private SearchResultsFactory           $searchResultsFactory,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
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
    public function save(Pairing $pairing)
    {
        try {
            $this->resourceModel->save($pairing);
        } catch (ConnectionException $exception) {
            throw new CouldNotSaveException(
                __('Database connection error'),
                $exception,
                $exception->getCode()
            );
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException(__('Unable to save item'), $e);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $this->getById($pairing->getId());
    }

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var SearchResultsInterface $results */
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setTotalCount($collection->getSize());
        $results->setItems($collection->getData());

        return $results;
    }

    public function getByPairingId(string $id): ?Pairing
    {
        $criteria = $this->criteriaBuilder->addFilter('pairing_id', $id)->create();
        $items = $this->getList($criteria)->getItems();

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
        $this->setLock($pairing->getId(), 1);
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     */
    public function unlock(Pairing $pairing)
    {
        $this->setLock($pairing->getId(), 0);
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws AlreadyExistsException
     */
    private function setLock(string $id, int $value)
    {
        $clone = $this->factory->create();
        $clone->setData([
            'id' => $id,
            'lock' => $value
        ]);

        $this->save($clone);
    }
}
