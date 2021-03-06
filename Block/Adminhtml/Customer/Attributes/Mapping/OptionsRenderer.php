<?php
/**
 * BitTools Platform | B2W - Companhia Digital
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  BitTools
 * @package   BitTools_SkyHub
 *
 * @copyright Copyright (c) 2018 B2W Digital - BitTools Platform.
 *
 * Access https://ajuda.skyhub.com.br/hc/pt-br/requests/new for questions and other requests.
 */

namespace BitTools\SkyHub\Block\Adminhtml\Customer\Attributes\Mapping;

use BitTools\SkyHub\Api\CustomerAttributeMappingRepositoryInterface;
use BitTools\SkyHub\Api\CustomerAttributeMappingOptionsRepositoryInterface;

class OptionsRenderer extends \Magento\Backend\Block\Template
{
    protected $_customerMappingAttribute;
    protected $_customerAttributeMappingRepository;
    protected $_customerAttributeMappingOptionsRepository;
    protected $_customerHelper;

    /**
     * OptionsRenderer constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param CustomerAttributeMappingRepositoryInterface $customerAttributeMappingRepository
     * @param CustomerAttributeMappingOptionsRepositoryInterface $customerAttributeMappingOptionsRepository
     * @param \BitTools\SkyHub\Helper\Customer\Customer $customerHelper
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                CustomerAttributeMappingRepositoryInterface $customerAttributeMappingRepository,
                                CustomerAttributeMappingOptionsRepositoryInterface $customerAttributeMappingOptionsRepository,
                                \BitTools\SkyHub\Helper\Customer\Customer $customerHelper,
                                array $data = [])
    {
        $this->setTemplate('customer/attributes/mapping/optionsrenderer.phtml');
        parent::__construct($context, $data);

        $this->_customerAttributeMappingRepository = $customerAttributeMappingRepository;
        $this->_customerAttributeMappingOptionsRepository = $customerAttributeMappingOptionsRepository;
        $this->_customerHelper = $customerHelper;
    }

    /**
     * @return mixed
     */
    public function getMagentoAttribute()
    {
        return $this->getCustomerMappingAttribute()->setAttributeId($this->getRequest()->getParam('magento_attribute_id'))->getAttribute();
    }

    /**
     * @return mixed
     */
    public function getSubMappingAttributeOptions()
    {
        return $this->_customerAttributeMappingOptionsRepository
            ->getOptionsListByMappingId($this->getCustomerMappingAttribute()->getId());
    }

    /**
     * @return mixed
     */
    public function getCustomerMappingAttribute()
    {
        if (!$this->_customerMappingAttribute) {
            $attributeId = $this->getRequest()->getParam('mapping_attribute_id');
            $this->_customerMappingAttribute = $this->_customerAttributeMappingRepository->get($attributeId);
        }
        return $this->_customerMappingAttribute;
    }

    /**
     * @return mixed
     */
    public function getMagentoAttributeOptions()
    {
        return $this->_customerHelper->getAttributeOptions($this->getMagentoAttribute()->getAttributeCode());
    }
}
