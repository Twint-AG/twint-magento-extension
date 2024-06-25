<?php
namespace Twint\Core\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\searchResultsInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Twint\Core\Api\PairingRepositoryInterface;
use Twint\Core\Model\ResourceModel\Pairing as ResourceModel;
use Twint\Core\Model\ResourceModel\Pairing\CollectionFactory;
use Twint\Core\Model\ResourceModel\Pairing\Collection;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class PairingRepository implements PairingRepositoryInterface{

    public function __construct(
        private PairingFactory $factory,
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

    public function save(Pairing $pairing)
    {
        try {
            $GLOBALS['test'] = true;

            return $this->resourceModel->save($pairing);
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
        }catch (\Throwable $e){
            dd($e);
        }

        dd($pairing->getId());

        return $this->getById($pairing->getId());
    }

    public function getList(SearchCriteriaInterface $criteria)
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
}
