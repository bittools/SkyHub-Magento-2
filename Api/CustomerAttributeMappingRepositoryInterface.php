<?php

namespace BitTools\SkyHub\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CustomerAttributeMappingRepositoryInterface
{
    /**
     * Retrieve all attributes for entity type
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);


    /**
     * @param $mappingId
     *
     * @return mixed
     */
    public function get($mappingId);


    /**
     * @param Data\CustomerAttributeMappingInterface $mapping
     *
     * @return mixed
     */
    public function save(Data\CustomerAttributeMappingInterface $mapping);


    /**
     * @param Data\CustomerAttributeMappingInterface $mapping
     *
     * @return mixed
     */
    public function delete(Data\CustomerAttributeMappingInterface $mapping);


    /**
     * @param int $mappingId
     *
     * @return mixed
     */
    public function deleteById($mappingId);
}
