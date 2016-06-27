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

$categoryAttributes = array(
    'mp_link_c',
    'mp_subcategories_mpcc',
    'mp_subcategories_mp',
    'mp_staticblocks_sb3a',
    'mp_staticblocks_sb2a',
    'mp_staticblocks_sb1a',
    'mp_description_a',
    'mp_link_a',
    'mp_thumbnail_a',
    'mp_subcategories_smincpc',
    'mp_subcategories_sm',
    'mp_dropdown_rca',
    'mp_dropdown_lca',
);

foreach ($categoryAttributes as $attribute) {
    $setup->updateAttribute('catalog_category', $attribute, 'type', 'varchar');
}

$installer->endSetup();
?>

