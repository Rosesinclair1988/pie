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
class Miura_MenuPlus_Block_Navigation extends Mage_Catalog_Block_Navigation {

    protected $_cursor_cat = 0;
    protected $_navigation_model;

    public function __construct() {
        parent::__construct();

        $this->_navigation_model = Mage::getModel("menuplus/navigation");

        $this->addData(array(
            'cache_lifetime' => false,
            'cache_tags' => array('MIURA_MENUPLUS_' . Mage_Catalog_Model_Category::CACHE_TAG),
            'cache_key' => $this->getCacheKey(),
        ));
    }

    public function getCacheKey() {

        $idcats = explode('/', $this->getCurrentCategory()->getPath());
        $key = 0;

        for ($i = count($idcats); $i > 0; $i--) {
            if (isset($idcats[$i])) {
                $key = $idcats[$i];
                break;
            }
        }

        return 'MENUPLUS_' . Mage::app()->getStore()->getId()
                . '_' . (int) Mage::app()->getStore()->isCurrentlySecure()
                . '_' . Mage::getDesign()->getPackageName()
                . '_' . Mage::getDesign()->getTheme('template')
                . '_' . $key;
    }

    public function getJs() {
        return '
            <script type="text/javascript">
                document.observe("dom:loaded",function(){
                    this.menuplus = new MenuPlus();
                    this.menuplus.setEffect("' . $this->_navigation_model->getSetting('dropdown_dme') . '");
                    this.menuplus.setDuration("' . $this->_navigation_model->getSetting('dropdown_tmd') . '");
                    this.menuplus.attachHooks();
                    this.menuplus.deactivateMenu();
                });
            </script>
            ';
    }

    public function renderMenuHtml() {
        $baseCategories = $this->_navigation_model->getLevelOneCategories();
        $coreUrlHelper = Mage::helper('core/url');

        $html = '';
        $position = 0;

        $html .= $this->_renderMainBarLinks('left');

        //Liens Catégories level 1
        foreach ($baseCategories as $baseCategory) {
            $position = $position + 1;
            $isFirst = ($position == 1) ? true : false;
            $isLast = ($position == count($baseCategories)) ? true : false;
            $html .= $this->_renderLevelOne($baseCategory, $position, $isLast, $isFirst);
        }

        $html .= $this->_renderMainBarLinks('right');

        return $html;
    }

    public function getSkinClass() {
        $settingClass = $this->_navigation_model->getSetting("global_skin");
        if ($settingClass != 'clean') {
            return 'mddm-skin-' . $settingClass;
        }
        return false;
    }

    protected function _renderMainBarLinks($position) {
        $html = '';

        $tabLinks = $this->_navigation_model->getMainBarLinks($position);

        foreach ($tabLinks as $link) {
            $html .= '<div class="' . $link['class'] . '">';
            $html .= '<a href="' . $link['url'] . '"><span>' . $link['name'] . '</span></a>';
            $html .= '</div>';
        }
        return $html;
    }

    protected function _renderLevelOne($category, $position = 0, $isLast = false, $isFirst = false) {
        $children = $this->_navigation_model->getCategoryChildren($category);
        $hasChildren = (count($children) > 0);

        $html = '';

        $classes = array();
        $classes[] = 'level-1';
        $classes[] = 'nav-' . $position;

        if ($this->_navigation_model->isCategoryActive($category)) {
            $classes[] = 'active';
        }

        if ($isFirst && !$this->_navigation_model->getSetting("mainbar_hla")) {
            $classes[] = 'first';
        }

        if ($isLast) {
            $classes[] = 'last';
        }

        if ($hasChildren) {
            $classes[] = 'parent';
        }

        $classesHtml = implode(' ', $classes);
        $categoryName = $this->escapeHtml($category->getName());
        $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($category);
        $linkClickable = $this->_navigation_model->getSetting("link_c", $category);

        $html .= '<div class="' . $classesHtml . '">';

        if ($linkClickable)
            $html .= '<a href="' . $categoryUrl . '" title="' . $categoryName . '">' . $category->getTitleMenuplus() . '<span>' . $categoryName . '</span></a>';
        else
            $html .= '<span title="' . $categoryName . '">' . $category->getTitleMenuplus() . '<span>' . $categoryName . '</span></span>';


        //Drop Down
        $html .= $this->_renderDropDown($category);

        $html .= '</div>';

        return $html;
    }

    protected function _renderDropDown($category) {
        $html = '';

        $right = false;
        $middle = false;
        $left = false;

        //Col Left Init
        if ($this->_navigation_model->getSetting("dropdown_lca", $category))
            $left = $this->_renderSideElements($category, "left");
        //Col Middle Init
        if ($this->_navigation_model->getSetting("dropdown_mca", $category))
            $middle = $this->_renderColMiddle($category);
        //Col Right Init
        if ($this->_navigation_model->getSetting("dropdown_rca", $category))
            $right = $this->_renderSideElements($category, "right");

        if ($left || $middle || $right) {
            $html .= '<div class="mddm-dropdown">';
            $html .= '<table class="mddm-dropdown-container">';
            $html .= '<tr>';

            //Col Left
            if ($left) {
                $html .= '<td class="mddm-col mddm-col-left">';
                $html .= $left;
                $html .= '</td>';
            }

            //Col Middle
            if ($middle) {
                $html .= '<td class="mddm-col mddm-col-middle">';
                $html .= $middle;
                $html .= '</td>';
            }

            //Col Right
            if ($right) {
                $html .= '<td class="mddm-col mddm-col-right">';
                $html .= $right;
                $html .= '</td>';
            }

            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
        }

        return $html;
    }

    protected function _renderSideElements($category, $position) {
        $html = '';

        $elements = $this->_navigation_model->getColSideElements($category, $position);
        $first = true;

        foreach ($elements as $element) {
            switch ($element["name"]) {
                case "link_":
                    $html .= $this->_renderCategoryLink($category, $first);
                    break;
                case "thumbnail_":
                    $html .= $this->_renderCategoryThumbnail($category, $first);
                    break;
                case "description_":
                    $html .= $this->_renderCategoryDescription($category, $first);
                    break;
                case "staticblocks_sb1":
                case "staticblocks_sb2":
                case "staticblocks_sb3":
                    $html .= $this->_renderCategoryStaticBlock($element["name"], $category, $first);
                    break;
                default:
                    break;
            }
            if (!empty($html)) {
                $first = false;
            }
        }
        return $html;
    }

    protected function _renderColMiddle($category) {
        $html = '';

        if ($this->_navigation_model->getSetting("dropdown_mcc", $category) == "subcategories") {
            $nbCategories = count(Mage::getModel('catalog/category')->getCategories($category->getEntityId(), $this->_navigation_model->getSetting("subcategories_d", $category), true, true, false));
            if ($nbCategories) {
                $html .= '<table class="mddm-col-middle-cat">';
                $html .= '<tr>';
                $html .= '<td class="first-col">';
                $html .= $this->_renderSubCategories($category, 2, $nbCategories, $category);
                $html .= '</td>';
                $html .= '</tr>';
                $html .= '</table>';
                $this->_cursor_cat = 0;
            }
        } else {
            $html .= $this->_renderMiddleCmsContent($category);
        }

        return $html;
    }

    protected function _renderSubCategories($category, $level, $nbCategories, $main_category) {
        $html = '';

        $children = $this->_navigation_model->getCategoryChildren($category);
        $hasChildren = (count($children) > 0);

        $depth = $this->_navigation_model->getSetting("subcategories_d", $main_category);
        $structure = Mage::getStoreConfig('menuplus/subcategories/s');
        $manualPlacement = $this->_navigation_model->getSetting("subcategories_mp", $main_category);
        $nbCategoriesPerColumns = $this->_navigation_model->getSetting("subcategories_ncpc", $main_category);
        $showMore = $this->_navigation_model->getSetting("subcategories_sm", $main_category);
        $showMoreLimit = $this->_navigation_model->getSetting("subcategories_sml", $main_category);
        $showMoreInNbCategoriesPerColumns = $this->_navigation_model->getSetting("subcategories_smincpc", $main_category);

        if ($hasChildren && $depth >= ($level - 1)) {
            $i = 0;
            $isMaxChild = true;
            foreach ($children as $child) {
                $i++;
                if (!$showMore || $showMore && (($level - 1) < $depth || $i <= $showMoreLimit)) {

                    $classes = array();
                    $classes[] = 'level-' . $level;
                    if ($this->_navigation_model->isCategoryActive($child)) {
                        $classes[] = 'active';
                    }

                    $classesHtml = implode(' ', $classes);
                    $categoryName = $this->escapeHtml($child->getName());
                    $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($child);

                    if ($manualPlacement && $this->_navigation_model->getCategorySetting("subcategories_mpcc", $child)) {
                        $html .= "</td><td>";
                    }

                    $html .= '<div class="' . $classesHtml . '">';
                    $html .= '<a href="' . $categoryUrl . '" title="' . $categoryName . '">' . $categoryName . '</a>';

                    if ($structure == 2) {
                        $this->_cursor_cat++;
                        if (!$manualPlacement && ((($this->_cursor_cat % $nbCategoriesPerColumns) == 0) && $this->_cursor_cat != $nbCategories)) {
                            $html .= "</td><td>";
                        }
                        // Recursion tant qu'on n'a pas dépassé la profondeur maximale de l'arbre de catégorie
                        $nextLevel = $level + 1;
                        $htmlChildren = $this->_renderSubCategories($child, $nextLevel, $nbCategories, $main_category);

                        if (!empty($htmlChildren)) {
                            $html .= '<div class="level-' . $nextLevel . '-container">';
                            $html .= $htmlChildren;
                            $html .= '</div>';
                        }
                    }

                    $html .= '</div>';

                    //Lien en voir plus
                    if ($showMore && (($level - 1) >= $depth && $i == $showMoreLimit)) {

                        if ($showMoreInNbCategoriesPerColumns) {
                            $this->_cursor_cat++;
                            if (!$manualPlacement && ((($this->_cursor_cat % $nbCategoriesPerColumns) == 0) && $this->_cursor_cat != $nbCategories)) {
                                $html .= "</td><td>";
                            }
                        }

                        if ($isMaxChild) {
                            $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($category);
                            $html .= '<div class="level-' . $level . '"><a class="more" href=' . $categoryUrl . '><span>' . $this->__('Show More') . '</span></a></div>';
                        }
                        $isMaxChild = false;
                    }

                    if ($structure == 1) {
                        // Passage sur une deuxième liste si on dépasse la dernière catégorie d'un colonne et que ce n'est pas la dernière
                        $this->_cursor_cat++;
                        if (!$manualPlacement && ((($this->_cursor_cat % $nbCategoriesPerColumns) == 0) && $this->_cursor_cat != $nbCategories)) {
                            $html .= "</td><td>";
                        }

                        if ($structure == 1) {
                            // Recursion tant qu'on n'a pas dépassé la profondeur maximale de l'arbre de catégorie
                            $nextLevel = $level + 1;
                            $html .= $this->_renderSubCategories($child, $nextLevel, $nbCategories, $main_category);
                        }
                    }
                }
            }
        }

        return $html;
    }

    protected function _renderMiddleCmsContent($category) {
        $staticBlockIdentifier = $this->_navigation_model->getSetting("dropdown_mcsbi", $category);
        $blockHtml = $this->getLayout()->createBlock('cms/block')->setBlockId($staticBlockIdentifier)->toHtml();

        $html = '';

        if (!empty($blockHtml)) {
            $html .= '<div class="mddm-col-middle-static-block">';
            $html .= $blockHtml;
            $html .= '</div>';
        }

        return $html;
    }

    protected function _renderCategoryLink($category, $first) {
        $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($category);
        $categoyName = $this->escapeHtml($category->getName());
        $firstClass = ($first) ? 'first' : '';

        $html = '<div class="mddm-dropdown-block mddm-category-link ' . $firstClass . '">';
        $html .= '<a href="' . $categoryUrl . '" title="' . $categoyName . '">' . Mage::helper('menuplus')->getLabelCategoryLink($categoyName) . '</a>';
        $html .= '</div>';

        return $html;
    }

    protected function _renderCategoryThumbnail($category, $first) {
        $categoyThumbnailUrl = $this->_navigation_model->getResizedThumbnailUrl($category);
        $html = '';

        if ($categoyThumbnailUrl) {
            $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($category);
            $categoyName = $this->escapeHtml($category->getName());
            $firstClass = ($first) ? 'first' : '';

            $html .= '<div class="mddm-dropdown-block mddm-category-image ' . $firstClass . '">';
            $html .= '<a href="' . $categoryUrl . '" title="' . $categoyName . '">';
            $html .= '<img src="' . $categoyThumbnailUrl . '" alt="' . $categoyName . '" />';
            $html .= '</a>';
            $html .= '</div>';
        }
        return $html;
    }

    protected function _renderCategoryDescription($category, $first) {
        $categoryDescription = $this->helper('catalog/output')->categoryAttribute($category, $category->getDescription(), 'description');
        $firstClass = ($first) ? 'first' : '';

        $html = '';

        if (!empty($categoryDescription)) {
            $html .= '<div class="mddm-dropdown-block mddm-category-description ' . $firstClass . '">';
            $html .= $categoryDescription;
            $html .= '</div>';
        }

        return $html;
    }

    protected function _renderCategoryStaticBlock($staticBlockNumber, $category, $first) {
        $staticBlockIdentifier = $this->_navigation_model->getSetting($staticBlockNumber . "i", $category);
        $blockHtml = $this->getLayout()->createBlock('cms/block')->setBlockId($staticBlockIdentifier)->toHtml();

        $firstClass = ($first) ? 'first' : '';

        $html = '';

        if (!empty($blockHtml)) {
            $html .= '<div class="mddm-dropdown-block mddm-category-static-block mddm-category-' . $staticBlockNumber . ' ' . $firstClass . '">';
            $html .= $blockHtml;
            $html .= '</div>';
        }

        return $html;
    }

}

?>
