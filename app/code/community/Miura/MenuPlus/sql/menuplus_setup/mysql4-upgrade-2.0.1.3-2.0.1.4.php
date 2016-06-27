<?php

/**
 * Miura Menu+ Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Miura
 * @package    Miura_MenuPlus
 * @author     Clement Larduinat
 * @copyright  Copyright (c) 2011 Miura Conseil (http://www.miura-conseil.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
$installer->startSetup();
$setup = new Mage_Customer_Model_Entity_Setup('core_setup');

$attributeGroupName = 'Menu+';

$categoryAttributes = array(
    'mp_link_c' => array(
        'label' => 'Clickable Category Link',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
        'note' => 'Make the Category Link Clickable or not.',
        'position' => 43,
    ),
);

$defaultAttributes = array(
    'group' => $attributeGroupName,
    'type' => 'varchar',
    'backend' => '',
    'backend_model' => '',
    'frontend' => '',
    'label' => '',
    'input' => 'text',
    'frontend_class' => '',
    'source' => '',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible' => true,
    'required' => 0,
    'user_defined' => false,
    'default' => null,
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'note' => null,
    'visible_on_front' => true,
    'visible_in_advanced_search' => 1,
    'is_html_allowed_on_front' => 0,
    'is_wysiwyg_enabled' => false,
    'unique' => false,
    'frontend_input_renderer' => null,
    'position' => 0,
);

$category_entity_id = $setup->getEntityTypeId('catalog_category');
$attributeSetId = $setup->getDefaultAttributeSetId($category_entity_id);
$attributeGroupId = $setup->getAttributeGroupId($category_entity_id, $attributeSetId, $attributeGroupName);

foreach ($categoryAttributes as $keyAttr => $valueAttr) {
    $attrValues = array_merge($defaultAttributes, $valueAttr);
    $setup->addAttribute('catalog_category', $keyAttr, $attrValues);

    if ($attrValues['frontend_input_renderer']) {
        $setup->updateAttribute('catalog_category', $keyAttr, 'frontend_input_renderer', $attrValues['frontend_input_renderer']);
    }
    if ($attrValues['position']) {
        $setup->addAttributeToGroup($category_entity_id, $attributeSetId, $attributeGroupId, $keyAttr, $attrValues['position']);
        $setup->updateAttribute('catalog_category', $keyAttr, 'position', $attrValues['position']);
    }
}

$installer->endSetup();
?>
