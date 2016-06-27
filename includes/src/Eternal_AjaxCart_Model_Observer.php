<?php

class Eternal_AjaxCart_Model_Observer {

    public function addToCartEvent($observer) {

        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
            
        $is_ajax = Mage::app()->getFrontController()->getRequest()->getParam('ajaxcart');
        
        if (!$is_ajax)
            return;
            
        $request = Mage::app()->getFrontController()->getRequest();

        if (!$request->getParam('in_cart') && !$request->getParam('is_checkout')) {

            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);

            $_response = Mage::getModel('eternal_ajaxcart/response')
                    ->setProductName($observer->getProduct()->getName())
                    ->setMessage(Mage::helper('checkout')->__('%s was added to your shopping cart.', $observer->getProduct()->getName()));
                    
            if ( $request->getParam('return_url') ) {
				$_response->setRedirectUrl($request->getParam('return_url'));
			} else {
            $action = '<a href="'. Mage::helper('checkout/url')->getCheckoutUrl() .'" class="button">'. Mage::helper('checkout')->__('Checkout') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('checkout')->__('Continue') .'</a>';
            $_response->setAction($action);
			}

            //append updated blocks
            $_response->addUpdatedBlocks($_response);

            $_response->send();
        }
        if ($request->getParam('is_checkout')) {

            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);

            $_response = Mage::getModel('eternal_ajaxcart/response')
                    ->setProductName($observer->getProduct()->getName())
                    ->setMessage(Mage::helper('checkout')->__('%s was added to your shopping cart.', $observer->getProduct()->getName()));
                    
			if ( $request->getParam('return_url') ) {
				$_response->setRedirectUrl($request->getParam('return_url'));
			} else {
            $action = '<a href="'. Mage::helper('checkout/url')->getCheckoutUrl() .'" class="button">'. Mage::helper('checkout')->__('Checkout') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('checkout')->__('Continue') .'</a>';
            $_response->setAction($action);
			}

            $_response->send();
        }
    }

    public function updateItemEvent($observer) {

        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
        
        $is_ajax = Mage::app()->getFrontController()->getRequest()->getParam('ajaxcart');
        
        if (!$is_ajax)
            return;
        
        $request = Mage::app()->getFrontController()->getRequest();

        if (!$request->getParam('in_cart') && !$request->getParam('is_checkout')) {

            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);

            $_response = Mage::getModel('eternal_ajaxcart/response')
                    ->setMessage(Mage::helper('checkout')->__('%s was updated in your shopping cart.', $observer->getItem()->getProduct()->getName()));
                    
            $action = '<a href="'. Mage::helper('checkout/url')->getCheckoutUrl() .'" class="button">'. Mage::helper('checkout')->__('Checkout') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('checkout')->__('Continue') .'</a>';
        
            $_response->setAction($action);

            //append updated blocks
            $_response->addUpdatedBlocks($_response);

            $_response->send();
        }
        if ($request->getParam('is_checkout')) {

            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);

            $_response = Mage::getModel('eternal_ajaxcart/response')
                    ->setMessage(Mage::helper('checkout')->__('%s was updated in your shopping cart.', $observer->getItem()->getProduct()->getName()));
                    
            $action = '<a href="'. Mage::helper('checkout/url')->getCheckoutUrl() .'" class="button">'. Mage::helper('checkout')->__('Checkout') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('checkout')->__('Continue') .'</a>';
        
            $_response->setAction($action);

                    
            $_response->send();
        }
    }

    public function getConfigurableOptions($observer) {
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
        
        $is_ajax = Mage::app()->getFrontController()->getRequest()->getParam('ajaxcart');
        
        if (!$is_ajax)
            return;
        
        $_response = Mage::getModel('eternal_ajaxcart/response');

        $product = Mage::registry('current_product');
        if (!$product->isConfigurable() && !$product->getTypeId() == 'bundle'){return false;exit;}

        //append configurable options block
        $_response->addConfigurableOptionsBlock($_response);
        $_response->send();
    }

    public function getGroupProductOptions() {
        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
        
        $id = Mage::app()->getFrontController()->getRequest()->getParam('product');
        $options = Mage::app()->getFrontController()->getRequest()->getParam('super_group');

        if($id) {
            $product = Mage::getModel('catalog/product')->load($id);
            if($product->getData()) {
                if($product->getTypeId() == 'grouped' && !$options) {
                    $_response = Mage::getModel('eternal_ajaxcart/response');
                    Mage::register('product', $product);
                    Mage::register('current_product', $product);

                    //add group product's items block
                    $_response->addGroupProductItemsBlock($_response);
                    $_response->send();
                }
            }
        }
    }
    
    public function addToWishlistEvent($observer) {

        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
            
        $is_ajax = Mage::app()->getFrontController()->getRequest()->getParam('ajaxcart');
        
        if (!$is_ajax)
            return;
            
        $request = Mage::app()->getFrontController()->getRequest();

        $_response = Mage::getModel('eternal_ajaxcart/response')
                    ->setProductName($observer->getProduct()->getName())
                    ->setMessage(Mage::helper('checkout')->__('%1$s has been added to your wishlist.', $observer->getProduct()->getName()));
                    
        $action = '<a href="'. Mage::helper('wishlist')->getListUrl() .'" class="button">'. Mage::helper('wishlist')->__('Wishlist') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('wishlist')->__('Continue') .'</a>';
        
        $_response->setAction($action);

        //append updated blocks
        $_response->addUpdatedBlocks($_response);

        $_response->send();
    }

    public function addToCompareEvent($observer) {

        if (!Mage::helper('eternal_ajaxcart')->getConfig('general/enable'))
            return;
            
        $is_ajax = Mage::app()->getFrontController()->getRequest()->getParam('ajaxcart');
        
        if (!$is_ajax)
            return;
            
        $request = Mage::app()->getFrontController()->getRequest();

        $_response = Mage::getModel('eternal_ajaxcart/response')
                ->setProductName($observer->getProduct()->getName())
                ->setMessage(Mage::helper('catalog')->__('The product %s has been added to comparison list.', $observer->getProduct()->getName()));
        
        $action = '<a href="'. Mage::helper('catalog/product_compare')->getListUrl() .'" class="button">'. Mage::helper('catalog')->__('Compare') .'</a><a onclick="continueShopping()" class="button">'. Mage::helper('catalog')->__('Continue') .'</a>';
        
        $_response->setAction($action);

        //append updated blocks
        $_response->addUpdatedBlocks($_response);

        $_response->send();
    }
}