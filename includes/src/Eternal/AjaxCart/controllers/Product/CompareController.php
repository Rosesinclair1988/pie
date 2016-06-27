<?php

require_once 'Mage/Catalog/controllers/Product/CompareController.php';

class Eternal_AjaxCart_Product_CompareController extends Mage_Catalog_Product_CompareController {

    /**
     * Add product to comparison list
     */
    public function addAction() {
        
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return parent::addAction();
        
        if (!$this->getRequest()->getParam('ajaxcart')){
            return parent::addAction();
        }
        
        try {
            $_response = Mage::getModel('eternal_ajaxcart/response');                
            $json_messages = array();
            
            if ($productId = (int) $this->getRequest()->getParam('product')) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);

                if ($product->getId()/* && !$product->isSuper()*/) {
                    Mage::getSingleton('catalog/product_compare_list')->addProduct($product);
                    
                    Mage::getSingleton('core/session')->setRefererUrl($this->_getRefererUrl());
                    Mage::helper('catalog/product_compare')->calculate();
                    
                    $this->getLayout()->getUpdate()->addHandle('eternal_ajaxcart');
                    $this->loadLayout();

                    Mage::dispatchEvent('catalog_product_compare_add_product_complete', array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                    );  
                } else {
                    $_response->setError(true);
                    $json_messages[] = $this->__('Cannot specify product.');
                }
            } else {
                $_response->setError(true);
                $json_messages[] = $this->__('Product not found.');
            }
            $_response->setMessages($json_messages);
            $_response->send();
        } catch (Mage_Core_Exception $e) {
            $_response = Mage::getModel('eternal_ajaxcart/response');
            $_response->setError(true);

            $messages = array_unique(explode("\n", $e->getMessage()));
            $json_messages = array();
            foreach ($messages as $message) {
                $json_messages[] = Mage::helper('core')->escapeHtml($message);
            }
            
            $_response->setMessages($json_messages);
            $_response->send();
        } catch (Exception $e) {
            $_response = Mage::getModel('eternal_ajaxcart/response');
            $_response->setError(true);
            $_response->setMessage($this->__('Cannot add the product.'));
            $_response->send();
        }
    }

    /**
     * Remove product in comparison list
     */
    public function removeAction() {
        
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return parent::removeAction();
        
        $referer_url = Mage::getSingleton('core/session')->getRefererUrl();
        if ($referer_url) {
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_REFERER_URL, $referer_url);
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_BASE64_URL, '');
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED, '');
        }
        return parent::removeAction();
    }

    /**
     * Clear products in comparison list
     */
    public function clearAction() {
        
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return parent::clearAction();
        
        $referer_url = Mage::getSingleton('core/session')->getRefererUrl();
        if ($referer_url) {
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_REFERER_URL, $referer_url);
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_BASE64_URL, '');
            $this->getRequest()->setParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED, '');
        } 
        return parent::clearAction();
    }

}