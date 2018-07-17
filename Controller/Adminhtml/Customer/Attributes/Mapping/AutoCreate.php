<?php

/**
 * Proudly powered by Magentor CLI!
 * Version v0.1.0
 * Official Repository: http://github.com/tiagosampaio/magentor
 *
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 */

namespace BitTools\SkyHub\Controller\Adminhtml\Customer\Attributes\Mapping;

use BitTools\SkyHub\Api\CustomerAttributeMappingRepositoryInterface;
use BitTools\SkyHub\Api\CustomerAttributeMappingOptionsRepositoryInterface;
use BitTools\SkyHub\Helper\Context as HelperContext;
use Magento\Backend\App\Action\Context;

class AutoCreate extends AbstractMapping
{
    /** @var CustomerAttributeMappingOptionsRepositoryInterface */
    protected $_customerAttributeMappingOptionsRepository;

    /** @var \BitTools\SkyHub\Helper\Customer\Customer  */
    protected $_customerHelper;

    /**
     * AbstractMapping constructor.
     *
     * @param Context                                     $context
     * @param HelperContext                               $helperContext
     * @param CustomerAttributeMappingRepositoryInterface $customerAttributeMappingRepository
     */
    public function __construct(
        Context $context,
        HelperContext $helperContext,
        CustomerAttributeMappingRepositoryInterface $customerAttributeMappingRepository,
        CustomerAttributeMappingOptionsRepositoryInterface $customerAttributeMappingOptionsRepository,
        \BitTools\SkyHub\Helper\Customer\Customer $customerHelper
    )
    {
        parent::__construct($context, $helperContext, $customerAttributeMappingRepository);

        $this->_customerAttributeMappingOptionsRepository = $customerAttributeMappingOptionsRepository;
        $this->_customerHelper = $customerHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     *
     * @throws \Exception
     */
    public function execute()
    {
        $mappingId = $this->getRequest()->getParam('id');

        try {
            /** @var Mapping $mapping */
            $mapping = $this->customerAttributeMappingRepository->get($mappingId);
        } catch (\Exception $e) {
            return $this->redirectIndex();
        }

        $attribute = $this->loadCustomerAttribute($mapping->getSkyhubCode());

        if ($attribute) {
            $mapping->setAttributeId((int)$attribute->getId());

            $this->messageManager
                ->addWarningMessage(__('There was already an attribute with the code "%1".', $mapping->getSkyhubCode()))
                ->addSuccessMessage(__('The attribute was only mapped automatically.'));
        }

        if (!$attribute) {
            $config = [
                'label' => $mapping->getSkyhubLabel(),
                'type' => 'varchar',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => false,
                'position' => 999,
                'system' => 0,
                'note' => sprintf(
                    '%s. %s.',
                    'Created automatically by BSeller SkyHub module',
                    $mapping->getSkyhubDescription()
                )
            ];

            $installConfig = (array)$mapping->getAttributeInstallConfig();

            foreach ($installConfig as $configKey => $itemValue) {
                if (is_null($itemValue)) {
                    continue;
                }

                $config[$configKey] = $itemValue;
            }

            if (isset($installConfig['options'])) {
                $options = [];
                foreach ($installConfig['options'] as $key => $value) {
                    $options[$value['default_value']] = $value['skyhub_label'];
                }

                $config['option']['values'] = $options;
            }

            /** @var \BitTools\SkyHub\Helper\Catalog\Product\Attribute $helper */
            $helper = $this->_objectManager
                ->create(\BitTools\SkyHub\Helper\Customer\Attribute::class);

            /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
            $attribute = $helper->createCustomerAttribute($mapping->getSkyhubCode(), (array)$config);

            if (!$attribute || !$attribute->getId()) {
                $this->messageManager->addErrorMessage(__('There was a problem when trying to create the attribute.'));
                return $this->redirectIndex();
            }

            $mapping->setAttributeId((int)$attribute->getId());
        }

        $this->customerAttributeMappingRepository->save($mapping);

        //mapping the select options
        if (isset($installConfig['options'])) {
            $optionsMapping = $this->_customerAttributeMappingOptionsRepository->getOptionsListByMappingId($mappingId);
            foreach ($optionsMapping as $optionMapping) {
                $attributeOptions = $attribute->getSource()->getAllOptions(false);

                foreach ($attributeOptions as $attributeOption) {
                    if ($attributeOption['label'] == $optionMapping->getSkyhubLabel()) {
                        $optionMapping->setMagentoValue($attributeOption['value']);
                        $this->_customerAttributeMappingOptionsRepository->save($optionMapping);
                        break;
                    }
                }
            }
        }
        //end

        $message = __(
            'The attribute "%1" was created in Magento and associated to SkyHub attribute "%2" automatically.',
            $attribute->getAttributeCode(),
            $mapping->getSkyhubCode()
        );

        $this->messageManager->addSuccessMessage($message);

        return $this->redirectIndex();
    }


    /**
     * @param string $code
     *
     * @return \Magento\Eav\Model\Entity\Attribute|null
     */
    protected function loadCustomerAttribute($code)
    {
        /** @var \Magento\Eav\Model\AttributeRepository $repository */
        $repository = $this->_objectManager->create(\Magento\Eav\Model\AttributeRepository::class);

        try {
            /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
            $attribute = $repository->get(\Magento\Customer\Model\Customer::ENTITY, $code);
        } catch (\Exception $e) {
            return null;
        }

        return $attribute;
    }
}
