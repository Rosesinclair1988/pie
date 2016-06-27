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
    /* Drop Down */
    'mp_dropdown_lca' => array(
        'label' => 'Show Left Column',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_dropdown_rca' => array(
        'label' => 'Show Right Column',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_dropdown_mcc' => array(
        'label' => 'Middle Column Content',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_dropdown_middlecolumncontent',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_dropdown_mcsbi' => array(
        'label' => 'Middle Column Static Block Identifier',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_staticblock',
        'note' => 'Shown if the Middle Column Content option is Static Block',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    /* Sub Categories */
    'mp_subcategories_d' => array(
        'label' => 'Depth',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_subcategories_depth',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_subcategories_ncpc' => array(
        'label' => 'Nb Categories per Columns',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    'mp_subcategories_sm' => array(
        'label' => 'View More Link',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
        'note' => 'Show a View More Link after a fixed number of sub-categories',
    ),
    'mp_subcategories_sml' => array(
        'label' => 'Number of sub-categories Limit',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
        'note' => 'Limit before the View More Link',
    ),
    'mp_subcategories_smincpc' => array(
        'label' => 'View More in sub-categories Columns',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
        'note' => 'Put View More Link in Nb Categories per Columns counter',
    ),
    /* Thumbnail */
    'mp_thumbnail_a' => array(
        'label' => 'Show Thumbnail',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_thumbnail_p' => array(
        'label' => 'Thumbnail Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_thumbnail_w' => array(
        'label' => 'Thumbnail Width',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'note' => 'In pixel',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    'mp_thumbnail_h' => array(
        'label' => 'Thumbnail Height',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'note' => 'In pixel. 0 for height auto',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    'mp_thumbnail_o' => array(
        'label' => 'Thumbnail Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    /* Category Link */
    'mp_link_a' => array(
        'label' => 'Show Category Link',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_link_p' => array(
        'label' => 'Category Link Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_link_o' => array(
        'label' => 'Category Link Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    /* Category Description */
    'mp_description_a' => array(
        'label' => 'Show Description',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_description_p' => array(
        'label' => 'Description Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_description_o' => array(
        'label' => 'Description Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    /* Static Blocks */
    'mp_staticblocks_sb1a' => array(
        'label' => 'Show Static Block 1',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb1i' => array(
        'label' => 'Static Block 1 Identifier',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_staticblock',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb1p' => array(
        'label' => 'Static Block 1 Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb1o' => array(
        'label' => 'Static Block 1 Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    'mp_staticblocks_sb2a' => array(
        'label' => 'Show Static Block 2',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb2i' => array(
        'label' => 'Static Block 2 Identifier',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_staticblock',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb2p' => array(
        'label' => 'Static Block 2 Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb2o' => array(
        'label' => 'Static Block 2 Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
    ),
    'mp_staticblocks_sb3a' => array(
        'label' => 'Show Static Block 3',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb3i' => array(
        'label' => 'Static Block 3 Identifier',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_staticblock',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb3p' => array(
        'label' => 'Static Block 3 Position',
        'input' => 'select',
        'type' => 'varchar',
        'source' => 'menuplus/adminhtml_system_source_position',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_select',
    ),
    'mp_staticblocks_sb3o' => array(
        'label' => 'Static Block 3 Order',
        'input' => 'text',
        'type' => 'varchar',
        'frontend_class' => 'validate-zero-or-greater',
        'frontend_input_renderer' => 'menuplus/adminhtml_helper_form_text',
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
);

foreach ($categoryAttributes as $keyAttr => $valueAttr) {
    $attrValues = array_merge($defaultAttributes, $valueAttr);
    $setup->addAttribute('catalog_category', $keyAttr, $attrValues);
    
    if($attrValues['frontend_input_renderer']){
        $setup->updateAttribute('catalog_category',$keyAttr,'frontend_input_renderer',$attrValues['frontend_input_renderer']);
    }
    
}

$installer->endSetup();
?>
