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
        'position' => 10,
    ),
    'mp_dropdown_rca' => array(
        'position' => 11,
    ),
    'mp_dropdown_mcc' => array(
        'position' => 12,
    ),
    'mp_dropdown_mcsbi' => array(
        'position' => 13,
    ),
    /* Sub Categories */
    'mp_subcategories_d' => array(
        'position' => 20,
    ),
    'mp_subcategories_ncpc' => array(
        'position' => 23,
    ),
    'mp_subcategories_sm' => array(
        'position' => 24,
    ),
    'mp_subcategories_sml' => array(
        'position' => 25,
    ),
    'mp_subcategories_smincpc' => array(
        'position' => 26,
    ),
    /* Thumbnail */
    'mp_thumbnail_a' => array(
        'position' => 30,
    ),
    'mp_thumbnail_p' => array(
        'position' => 31,
    ),
    'mp_thumbnail_w' => array(
        'position' => 32,
    ),
    'mp_thumbnail_h' => array(
        'position' => 33,
    ),
    'mp_thumbnail_o' => array(
        'position' => 34,
    ),
    /* Category Link */
    'mp_link_a' => array(
        'position' => 40,
    ),
    'mp_link_p' => array(
        'position' => 41,
    ),
    'mp_link_o' => array(
        'position' => 42,
    ),
    /* Category Description */
    'mp_description_a' => array(
        'position' => 50,
    ),
    'mp_description_p' => array(
        'position' => 51,
    ),
    'mp_description_o' => array(
        'position' => 52,
    ),
    /* Static Blocks */
    'mp_staticblocks_sb1a' => array(
        'position' => 60,
    ),
    'mp_staticblocks_sb1i' => array(
        'position' => 61,
    ),
    'mp_staticblocks_sb1p' => array(
        'position' => 62,
    ),
    'mp_staticblocks_sb1o' => array(
        'position' => 63,
    ),
    'mp_staticblocks_sb2a' => array(
        'position' => 64,
    ),
    'mp_staticblocks_sb2i' => array(
        'position' => 65,
    ),
    'mp_staticblocks_sb2p' => array(
        'position' => 66,
    ),
    'mp_staticblocks_sb2o' => array(
        'position' => 67,
    ),
    'mp_staticblocks_sb3a' => array(
        'position' => 68,
    ),
    'mp_staticblocks_sb3i' => array(
        'position' => 69,
    ),
    'mp_staticblocks_sb3p' => array(
        'position' => 70,
    ),
    'mp_staticblocks_sb3o' => array(
        'position' => 71,
    ),

);

$category_entity_id = $setup->getEntityTypeId('catalog_category');
$attributeSetId = $setup->getDefaultAttributeSetId($category_entity_id);
$attributeGroupId = $setup->getAttributeGroupId($category_entity_id,$attributeSetId,$attributeGroupName);

foreach ($categoryAttributes as $keyAttr => $valueAttr) {
    if($valueAttr['position']){
        $setup->addAttributeToGroup($category_entity_id,$attributeSetId,$attributeGroupId,$keyAttr,$valueAttr['position']);
        $setup->updateAttribute('catalog_category',$keyAttr,'position',$valueAttr['position'],$valueAttr['position']);
    }
}

$installer->endSetup();
?>
