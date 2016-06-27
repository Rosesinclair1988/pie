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
class Miura_MenuPlus_Model_Navigation extends Mage_Core_Model_Abstract {

    protected $_settings;

    public function _construct() {
        parent::_construct();
        $this->initSettings();
    }

    public function getLevelOneCategories() {
        $baseCategories = array();
        foreach (Mage::helper('catalog/category')->getStoreCategories() as $baseCategory) {
            if ($baseCategory->getIsActive()) {
                $_category = Mage::getModel('catalog/category')->load($baseCategory->getID());
                if (!$this->magentoIsSup15() || ($this->magentoIsSup15() && $_category->getIncludeInMenu())) {
                    $baseCategories[] = $_category;
                }
            }
        }
        return $baseCategories;
    }

    public function getMainBarLinks($position) {
        $coreUrlHelper = Mage::helper('core/url');
        $tabLinks = array();

        if ($position == 'left' && $this->getSetting("mainbar_hla")) {
            $url = $coreUrlHelper->getHomeUrl();
            $activeClass = ($coreUrlHelper->getCurrentUrl() === $url) ? 'active' : '';
            $class = 'home-link level-1 ' . $activeClass;
            $name = Mage::helper('cms')->__("Home");

            $tabLinks[] = array("url" => $url, "class" => $class, "name" => $name, "order" => 0);
        }

        for ($i = 1; $i < 4; $i++) {
            $tmp_link = $this->getMainBarCmsLink($i, $position);
            if ($tmp_link) {
                $tabLinks[] = $tmp_link;
            }

            $tmp_link = $this->getMainBarCustomLink($i, $position);
            if ($tmp_link) {
                $tabLinks[] = $tmp_link;
            }
        }

        // Classe les élements du tableau suivant la valeur de la colonne "order" (Source : http://www.php.net/manual/fr/function.array-multisort.php#97766)
        if (!empty($tabLinks)) {
            $tmp = Array();
            foreach ($tabLinks as &$ma)
                $tmp[] = &$ma["order"];
            array_multisort($tmp, $tabLinks);

            if ($position == 'left') {
                $tabLinks[0]["class"] .= ' first';
            }
        }

        return $tabLinks;
    }

    protected function getMainBarCmsLink($cmsLinkIdentifier, $position) {
        if (($this->getSetting('mainbar_cl' . $cmsLinkIdentifier . 'i') != '') && ($this->getSetting('mainbar_cl' . $cmsLinkIdentifier . 'p') == $position)) {
            $page = Mage::getModel('cms/page');
            $page->setStoreId(Mage::app()->getStore()->getId());
            if ($page->load($this->getSetting('mainbar_cl' . $cmsLinkIdentifier . 'i'))) {
                $url = Mage::getUrl(null, array('_direct' => $page->getIdentifier()));
                $activeClass = (Mage::helper('core/url')->getCurrentUrl() === $url) ? 'active' : '';
                $class = 'cms-link-' . $page->getIdentifier() . ' level-1 ' . $activeClass;
                $name = $page->getTitle();
                $order = $this->getSetting('mainbar_cl' . $cmsLinkIdentifier . 'o');

                return (array("url" => $url, "class" => $class, "name" => $name, "order" => $order));
            }
        }
        return false;
    }

    protected function getMainBarCustomLink($cmsLinkIdentifier, $position) {
        if (($this->getSetting('mainbar_cusl' . $cmsLinkIdentifier . 'u') != '') && ($this->getSetting('mainbar_cusl' . $cmsLinkIdentifier . 'p') == $position)) {
            $url = $this->getSetting('mainbar_cusl' . $cmsLinkIdentifier . 'u');
            $url = str_replace('{store_url}', Mage::getUrl(), $url);

            $activeClass = (Mage::helper('core/url')->getCurrentUrl() === $url) ? 'active' : '';
            $class = 'custom-link-' . $cmsLinkIdentifier . ' level-1 ' . $activeClass;
            $name = $this->getSetting('mainbar_cusl' . $cmsLinkIdentifier . 't');
            $order = $this->getSetting('mainbar_cusl' . $cmsLinkIdentifier . 'o');

            return (array("url" => $url, "class" => $class, "name" => $name, "order" => $order));
        }
        return false;
    }

    public function getCurrentCategory() {
        if (Mage::getSingleton('catalog/layer')) {
            return Mage::getSingleton('catalog/layer')->getCurrentCategory();
        }
        return false;
    }

    public function isCategoryActive($category) {
        $currentCategory = $this->getCurrentCategory();
        if ($currentCategory) {
            return in_array($category->getId(), $currentCategory->getPathIds());
        }
        return false;
    }

    public function getCategoryChildren($category) {
        $children = $category->getChildrenCategories();

        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $_category = Mage::getModel('catalog/category')->load($child->getID());
                if (!$this->magentoIsSup15() || ($this->magentoIsSup15() && $_category->getIncludeInMenu())) {
                    $activeChildren[] = $_category;
                }
            }
        }
        return ($activeChildren);
    }

    protected function magentoIsSup15() {
        $magentoVersion = Mage::getVersionInfo();
        return (intval($magentoVersion['major'] . $magentoVersion['minor']) >= 15);
    }

    // Source : http://www.magentocommerce.com/boards/viewthread/17187/#t278918
    public function getResizedThumbnailUrl($category, $quality = 100) {

        // Les miniatures de catégories sont apparues avec magento 1.5, on prend l'image de la catégorie pour les versions antérieures
        $magentoVersion = Mage::getVersionInfo();
        $image = ($this->magentoIsSup15()) ? $category->getThumbnail() : $category->getImage();

        if (!$image)
            return false;

        $imageUrl = Mage::getBaseDir('media') . DS . "catalog" . DS . "category" . DS . $image;
        if (!is_file($imageUrl))
            return false;

        $imageResized = Mage::getBaseDir('media') . DS . "catalog" . DS . "product" . DS . "cache" . DS . "cat_resized" . DS . $image; // Because clean Image cache function works in this folder only
        if (!file_exists($imageResized) && file_exists($imageUrl) || file_exists($imageUrl) && filemtime($imageUrl) > filemtime($imageResized)) :
            $imageObj = new Varien_Image($imageUrl);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(false);
            $imageObj->quality($quality);

            $thumbnailH = $this->getSetting("thumbnail_h", $category);
            $thumbnailW = $this->getSetting("thumbnail_w", $category);
            if ($thumbnailH == 0) {
                $imageObj->resize($thumbnailW);
            } else {
                $imageObj->resize($thumbnailW, $thumbnailH);
            }

            $imageObj->save($imageResized);
        endif;

        if (file_exists($imageResized)) {
            return Mage::getBaseUrl('media') . "catalog/product/cache/cat_resized/" . $image;
        } else {
            return Mage::getBaseUrl('media') . 'catalog/category/' . $image;
        }
    }

    public function getColSideElements($category, $position) {
        $elements = array();

        // Place dans un tableau tous les élements pouvant etre dans une des positions
        $attributesSide = array("thumbnail_", "link_", "description_", "staticblocks_sb1", "staticblocks_sb2", "staticblocks_sb3");
        foreach ($attributesSide as $attribute) {
            if ($this->getSetting($attribute . "a", $category) && $this->getSetting($attribute . "p", $category) == $position) {
                array_push($elements, array("name" => $attribute, "order" => $this->getSetting($attribute . "o", $category)));
            }
        }

        // Classe les élements du tableau suivant la valeur de la colonne "order" (Source : http://www.php.net/manual/fr/function.array-multisort.php#97766)
        if (!empty($elements)) {
            $tmp = Array();
            foreach ($elements as &$ma)
                $tmp[] = &$ma["order"];
            array_multisort($tmp, $elements);
        }

        return $elements;
    }

    public function getSetting($setting, $category = null) {
        if ($category) {
            $categorySetting = $this->getCategorySetting($setting, $category);
            if ($categorySetting != '') {
                return $categorySetting;
            }
        }
        return $this->_settings["mp_" . $setting];
    }

    public function getCategorySetting($setting, $category) {
        return $category->getData("mp_" . $setting);
    }

    protected function initSettings() {

        $settings = array(
            'mp_global_skin',
            'mp_dropdown_lca',
            'mp_dropdown_mca',
            'mp_dropdown_rca',
            'mp_dropdown_mcc',
            'mp_dropdown_mcsbi',
            'mp_dropdown_dme',
            'mp_dropdown_tmd',
            'mp_mainbar_hla',
            'mp_mainbar_cl1i',
            'mp_mainbar_cl1p',
            'mp_mainbar_cl1o',
            'mp_mainbar_cl2i',
            'mp_mainbar_cl2p',
            'mp_mainbar_cl2o',
            'mp_mainbar_cl3i',
            'mp_mainbar_cl3p',
            'mp_mainbar_cl3o',
            'mp_mainbar_cusl1u',
            'mp_mainbar_cusl1t',
            'mp_mainbar_cusl1p',
            'mp_mainbar_cusl1o',
            'mp_mainbar_cusl2u',
            'mp_mainbar_cusl2t',
            'mp_mainbar_cusl2p',
            'mp_mainbar_cusl2o',
            'mp_mainbar_cusl3u',
            'mp_mainbar_cusl3t',
            'mp_mainbar_cusl3p',
            'mp_mainbar_cusl3o',
            'mp_subcategories_d',
            'mp_subcategories_s',
            'mp_subcategories_mp',
            'mp_subcategories_ncpc',
            'mp_subcategories_sm',
            'mp_subcategories_sml',
            'mp_subcategories_smincpc',
            'mp_thumbnail_a',
            'mp_thumbnail_p',
            'mp_thumbnail_w',
            'mp_thumbnail_h',
            'mp_thumbnail_o',
            'mp_link_a',
            'mp_link_p',
            'mp_link_o',
            'mp_link_c',
            'mp_description_a',
            'mp_description_p',
            'mp_description_o',
            'mp_staticblocks_sb1a',
            'mp_staticblocks_sb1i',
            'mp_staticblocks_sb1p',
            'mp_staticblocks_sb1o',
            'mp_staticblocks_sb2a',
            'mp_staticblocks_sb2i',
            'mp_staticblocks_sb2p',
            'mp_staticblocks_sb2o',
            'mp_staticblocks_sb3a',
            'mp_staticblocks_sb3i',
            'mp_staticblocks_sb3p',
            'mp_staticblocks_sb3o',
        );


        $this->_settings = array();

        foreach ($settings as $setting) {
            $this->_settings[$setting] = Mage::getStoreConfig(str_replace(array('mp', '_'), array('menuplus', '/'), $setting));
        }
    }

}
