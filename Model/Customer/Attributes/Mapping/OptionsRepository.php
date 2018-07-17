<?php

namespace BitTools\SkyHub\Model\Customer\Attributes\Mapping;

use BitTools\SkyHub\Api\Data;
use BitTools\SkyHub\Api\CustomerAttributeMappingOptionsRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \BitTools\SkyHub\Model\ResourceModel\Customer\Attributes\Mapping\Options\CollectionFactory;

class OptionsRepository implements CustomerAttributeMappingOptionsRepositoryInterface
{

    /** @var OptionsFactory */
    protected $_optionsFactory;
    protected $_collectionFactory;


    public function __construct(OptionsFactory $optionsFactory,
                                CollectionFactory $_collectionFactory)
    {
        $this->_optionsFactory = $optionsFactory;
        $this->_collectionFactory = $_collectionFactory;
    }


    /**
     * Retrieve all attributes for entity type
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        // TODO: Implement getList() method.
    }


    /**
     * @param $mappingId
     *
     * @return Mapping|mixed
     *
     * @throws NoSuchEntityException
     */
    public function get($mappingId)
    {
        /** @var Mapping $mapping */
        $mapping = $this->_optionsFactory->create();
        $mapping->load($mappingId);

        if (!$mapping->getId()) {
            throw new NoSuchEntityException(__('Attribute Mapping with id "%1" does not exist.', $mappingId));
        }

        return $mapping;
    }

    /**
     * @param $mappingId
     * @return mixed
     */
    public function getOptionsListByMappingId($mappingId)
    {
        $collection = $this->_collectionFactory->create();
        return $collection->addFieldToFilter('customer_attributes_mapping_id', $mappingId);
    }


    /**
     * @param Data\CustomerAttributeMappingOptionsInterface $object
     * @return $this
     */
    public function save(Data\CustomerAttributeMappingOptionsInterface $object)
    {
        $object->save();
        return $this;
    }

    /**
     * @param Data\CustomerAttributeMappingOptionsInterface $object
     * @return $this
     */
    public function delete(Data\CustomerAttributeMappingOptionsInterface $object)
    {
        $object->delete();
        return $this;
    }


    /**
     * @param int $mappingId
     *
     * @return mixed
     */
    public function deleteById($mappingId)
    {
        /** @var Mapping $mapping */
        $mapping = $this->mappingFactory->create();
        $mapping->setId($mappingId);
        $this->delete($mapping);

        return $this;
    }
}
