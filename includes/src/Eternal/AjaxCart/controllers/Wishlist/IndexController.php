<?php

require_once 'Mage/Wishlist/controllers/IndexController.php';

class Eternal_AjaxCart_Wishlist_IndexController extends Mage_Wishlist_IndexController {

    public function preDispatch() {
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return parent::preDispatch();
        
        if (!$this->getRequest()->getParam('ajaxcart')){
            return parent::preDispatch();
        }
        
        $this->_skipAuthentication = true;        
        return parent::preDispatch();
    }
    
    /**
     * Add product to wishlist
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
            
            if (!Mage::getStoreConfigFlag('wishlist/general/active')) {
                $_response->setError(true);
                $json_messages[] = $this->__('Wishlist has been disabled by admin.');
            }
            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                $_response->setError(true);
                $json_messages[] = $this->__('Please login first.');
            }
            
            if (empty($json_messages)) {
                $wishlist = $this->_getWishlist();
                if (!$wishlist) {
                    $_response->setError(true);
                    $json_messages[] = $this->__('Unable to create wishlist.');
                } else {
                    $productId = (int) $this->getRequest()->getParam('product');
                    if (!$productId) {
                        $_response->setError(true);
                        $json_messages[] = $this->__('Product not found.');
                    } else {
                        $product = Mage::getModel('catalog/product')->load($productId);
                        if (!$product->getId() || !$product->isVisibleInCatalog()) {
                            $_response->setError(true);
                            $json_messages[] = $this->__('Cannot specify product.');
                        } else {
                            $requestParams = $this->getRequest()->getParams();
                            $buyRequest = new Varien_Object($requestParams);

                            $result = $wishlist->addNewItem($product, $buyRequest);
                            if (is_string($result)) {
                                Mage::throwException($result);
                            }
                            $wishlist->save();

                            Mage::dispatchEvent(
                                'wishlist_add_product',
                                array(
                                    'wishlist'  => $wishlist,
                                    'product'   => $product,
                                    'item'      => $result
                                )
                            );

                            Mage::helper('wishlist')->calculate();

                            $message = $this->__('%1$s has been added to your wishlist.', $product->getName(), $referer);
                            
                            Mage::unregister('wishlist');
                            Mage::getSingleton('core/session')->setRefererUrl($this->_getRefererUrl());
                            
                            $this->getLayout()->getUpdate()->addHandle('eternal_ajaxcart');
                            $this->loadLayout();

                            Mage::dispatchEvent('wishlist_index_add_product_complete', array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                            );
                        }
                    }
                }
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
}