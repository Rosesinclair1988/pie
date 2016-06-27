<?php

/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_SeoSuite
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * SEO Suite extension
 *
 * @category   MageWorx
 * @package    MageWorx_SeoSuite
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_SeoSuite_Block_Page_Html_Head extends MageWorx_SeoSuite_Block_Page_Html_Head_Abstract {

    public function getCssJsHtml() {
        if ($this->setCanonicalUrl() || empty($this->_data['canonical_url'])) {
            return parent::getCssJsHtml();
        }
        $html = '<link rel="canonical" href="' . $this->_data['canonical_url'] . '" />' . "\n";
        $html .= parent::getCssJsHtml();
        return $html;
    }

    public function setCanonicalUrl() {
        if (!Mage::getStoreConfig('mageworx_seo/seosuite/enabled')) {
            return;
        }

        $canonicalUrl = null;

        $productActions = array(
            'catalog_product_view',
            'review_product_list',
            'review_product_view',
            'productquestions_show_index',
        );

        if (empty($this->_data['canonical_url'])) {
            if (in_array($this->getAction()->getFullActionName(), array_filter(preg_split('/\r?\n/', Mage::getStoreConfig('mageworx_seo/seosuite/ignore_pages'))))) {
                return;
            } elseif (in_array($this->getAction()->getFullActionName(), $productActions)) {
                $useCategories = Mage::getStoreConfigFlag('catalog/seo/product_use_categories');
                if ($product = Mage::registry('current_product')) {
                    if ($canonicalUrl = $product->getCanonicalUrl()) {
                        $urlRewrite = Mage::getModel('core/url_rewrite')->loadByIdPath($canonicalUrl);
                        $canonicalUrl = Mage::getUrl('') . $urlRewrite->getRequestPath();
                    } else {
                        $productCanonicalUrl = Mage::getStoreConfig('mageworx_seo/seosuite/product_canonical_url');
                        $dir = ($productCanonicalUrl == 2) ? 'asc' : 'desc';
                        $collection = Mage::getResourceModel('seosuite/core_url_rewrite_collection')
                                        ->filterAllByProductId($product->getId())
                                        ->sortByLength($dir)
                                        ->addStoreFilter(Mage::app()->getStore()->getId(), false);
                        if ($urlRewrite = $collection->getFirstItem()) {
                            $canonicalUrl = Mage::getUrl('') . $urlRewrite->getRequestPath();
                        }

                        if (!$canonicalUrl) {
                            $canonicalUrl = $product->getProductUrl(false);
                            if (!$canonicalUrl || $productCanonicalUrl == 0) {
                                $product->setDoNotUseCategoryId(!$useCategories);
                                $canonicalUrl = $product->getProductUrl(false);
                            }
                        }
                    }
                }
            } else {
                $url = Mage::helper('core/url')->getCurrentUrl();
                $parsedUrl = parse_url($url);
                extract($parsedUrl);
                $canonicalUrl = $scheme . '://' . $host . (isset($port) && '80' != $port ? ':' . $port : '') . $path;
            }
            if ($canonicalUrl) {
                if (Mage::getStoreConfig('mageworx_seo/seosuite/trailing_slash')) {
                    if ('/' != substr($canonicalUrl, -1) && !in_array(substr(strrchr($canonicalUrl, '.'), 1), array('rss', 'html', 'htm', 'xml', 'php'))) {
                        $canonicalUrl .= '/';
                    }
                }
            }
            $this->_data['canonical_url'] = $canonicalUrl;
        }
        if (method_exists($this, 'addLinkRel') && !empty($this->_data['canonical_url'])) {
            $this->addLinkRel('canonical', $this->_data['canonical_url']);
            return true;
        }
    }

    public function getRobots() {
        $noindexPatterns = explode(',', Mage::getStoreConfig('mageworx_seo/seosuite/noindex_pages'));
        foreach ($noindexPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/', $this->getAction()->getFullActionName())) {
                $this->_data['robots'] = 'NOINDEX, FOLLOW';
                break;
            }
        }
        $noindexPatterns = array_filter(preg_split('/\r?\n/', Mage::getStoreConfig('mageworx_seo/seosuite/noindex_pages_user')));
        foreach ($noindexPatterns as $pattern) {
            $pattern = str_replace('*', '.*?', $pattern);
            if (preg_match('%' . $pattern . '%', $this->getAction()->getFullActionName()) || preg_match('%' . $pattern . '%', $this->getAction()->getRequest()->getRequestString())) {
                $this->_data['robots'] = 'NOINDEX, FOLLOW';
                break;
            }
        }
        if (empty($this->_data['robots'])) {
            $this->_data['robots'] = Mage::getStoreConfig('design/head/default_robots');
        }


        return $this->_data['robots'];
    }

    public function getDescription() {
       	$oldDescription = empty($this->_data['description']) ? Mage::getStoreConfig('design/head/default_description') : $this->_data['description'];
        $this->_data['description'] = '';
        if ($this->_product = Mage::registry('current_product')) {
            if ($this->_product->getMetaDescription()) {
                $this->_data['description'] = $this->_product->getMetaDescription();
            } else {
                $descriptionTemplate = Mage::getStoreConfig('mageworx_seo/seosuite/product_meta_description_template');
                if ($descriptionTemplate) {
                    $template = Mage::getModel('seosuite/catalog_product_template_title');
                    $template->setTemplate($descriptionTemplate)
                            ->setProduct($this->_product);
                    $this->_data['description'] = $template->process();
                }
                if (empty($this->_data['description'])) {
                    $shortDescription = $this->getProductDescription();
                    if (Mage::getStoreConfigFlag('mageworx_seo/seosuite/product_meta_description') && !empty($shortDescription)) {
                        $this->_data['description'] = $shortDescription;
                    }
                }
            }
        }

        if (empty($this->_data['description'])) {
            $this->_data['description'] = $oldDescription;
        }

        $stripTags = new Zend_Filter_StripTags();

        return htmlspecialchars(html_entity_decode(preg_replace(array('/\r?\n/', '/[ ]{2,}/'), array(' ', ' '), $stripTags->filter($this->_data['description'])), ENT_QUOTES, 'UTF-8'));
    }

}
