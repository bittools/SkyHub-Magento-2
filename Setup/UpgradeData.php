<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace BitTools\SkyHub\Setup;

use BitTools\SkyHub\Functions;
use BitTools\SkyHub\Model\Customer\Attributes\Mapping;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use BitTools\SkyHub\Model\Config\SkyhubAttributes\Data as SkyhubConfigData;
use Magento\Eav\Model\ResourceModel\Entity\AttributeFactory;

/**
 * Upgrade Data script
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpgradeData implements UpgradeDataInterface
{
    use Functions, Setup;

    /**
     * EAV setup factory
     *
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    /** @var SkyhubConfigData */
    protected $skyhubConfigData;

    /** @var AttributeFactory */
    protected $attributeFactory;

    /**
     * Constructor
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param UpgradeWidgetData $upgradeWidgetData
     * @param UpgradeWebsiteAttributes $upgradeWebsiteAttributes
     */
    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        SkyhubConfigData $configData,
        AttributeFactory $attributeFactory
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->skyhubConfigData = $configData;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($context->getVersion()
            && version_compare($context->getVersion(), '1.0.1') < 0
        ) {
            $this->setup = $setup;
            $this->setup()->startSetup();

            /**
             * Install bseller_skyhub_customer_attributes_mapping data.
             */
            $this->installCustomerSkyHubRequiredAttributes();

            $this->setup()->endSetup();
        }
    }

    /**
     * Install SkyHub required attributes.
     *
     * @return $this
     */
    protected function installCustomerSkyHubRequiredAttributes()
    {
        $attributes = (array)  $this->skyhubConfigData->getEntityAttributes(\Magento\Customer\Model\Customer::ENTITY);
        $table      = (string) $this->getTable('bittools_skyhub_customer_attributes_mapping');

        /** @var array $attribute */
        foreach ($attributes as $identifier => $data) {
            $skyhubCode  = $this->arrayExtract($data, 'code');
            $label       = $this->arrayExtract($data, 'label');
            $castType    = $this->arrayExtract($data, 'cast_type', Mapping::DATA_TYPE_STRING);
            $description = $this->arrayExtract($data, 'description');
            $validation  = $this->arrayExtract($data, 'validation');
            $enabled     = (bool) $this->arrayExtract($data, 'required', true);
            $required    = (bool) $this->arrayExtract($data, 'required', true);
            $editable    = (bool) $this->arrayExtract($data, 'editable', true);
            $hasOptions = (bool)$this->arrayExtract($data, 'has_options', false);
            $createdAt = $this->now();

            if (empty($skyhubCode) || empty($castType)) {
                continue;
            }

            $attributeData = [
                'skyhub_code'        => $skyhubCode,
                'skyhub_label'       => $label,
                'skyhub_description' => $description,
                'enabled'            => $enabled,
                'cast_type'          => $castType,
                'validation'         => $validation,
                'required'           => $required,
                'editable'           => $editable,
                'created_at'         => $createdAt,
                'has_options'        => $hasOptions
            ];

            $installConfig = (array) $this->arrayExtract($data, 'attribute_install_config', []);
            $magentoCode   = $this->arrayExtract($installConfig, 'attribute_code');

            /** @var int $attributeId */
            if ($attributeId = (int) $this->getAttributeIdByCode($magentoCode)) {
                $attributeData['attribute_id'] = $attributeId;
            }

            $this->getConnection()->beginTransaction();

            try {
                /** @var \Magento\Framework\DB\Select $select */
                $select = $this->getConnection()
                    ->select()
                    ->from($table, 'id')
                    ->where('skyhub_code = :skyhub_code')
                    ->limit(1);

                $id = $this->getConnection()->fetchOne($select, [':skyhub_code' => $skyhubCode]);

                if ($id) {
                    $this->getConnection()->update($table, $attributeData, "id = {$id}");
                    $this->getConnection()->commit();
                    continue;
                }

                $this->getConnection()->insert($table, $attributeData);

                $parentAttributeId = $this->getConnection()->lastInsertId($table);
                $this->getConnection()->commit();

                /*
                 * if the attribute has options
                 */
                if (isset($attributeData['has_options']) && $attributeData['has_options']) {
                    $this->fillOptionsTable($this->arrayExtract($data, 'options', false), $parentAttributeId);
                }

            } catch (\Exception $e) {
                $this->getConnection()->rollBack();
            }
        }

        return $this;
    }

    /**
     * @param $code
     *
     * @return int|null
     */
    protected function getAttributeIdByCode($code)
    {
        $attributeId = null;

        try {
            /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute */
            $attribute   = $this->attributeFactory->create();
            $attributeId = $attribute->getIdByCode(\Magento\Customer\Model\Customer::ENTITY, $code);
        } catch (\Exception $e) {

        }

        return $attributeId;
    }

    protected function fillOptionsTable($options, $parentAttributeId)
    {
        $table = (string)$this->getTable('bittools_skyhub_customer_attributes_mapping_options');

        foreach ($options as $option) {
            try {
                $this->getConnection()->beginTransaction();

                $optionData =
                    [
                        'skyhub_code' => $this->arrayExtract($option, 'skyhub_code'),
                        'skyhub_label' => $this->arrayExtract($option, 'skyhub_label'),
                        'customer_attributes_mapping_id' => $parentAttributeId
                    ];
                $this->getConnection()->insert($table, $optionData);
                $this->getConnection()->commit();
            } catch (Exception $e) {
                $this->getConnection()->rollBack();
            }
        }
    }
}

