<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement Systempay
#						Version : 1.2 (révision 46959)
#									########################
#					Développé pour Magento
#						Version : 1.5.1.0
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						13/05/2013
#						Contact : supportvad@lyra-network.com
#
#####################################################################################################

class Lyra_Systempay_Block_Redirect extends Mage_Core_Block_Template {
	
    public function _construct() {
    	
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckout()  {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Systempay payment API instance
     *
     * @return Lyra_Systempay_Model_Payment
     */
    private function _getPayment() {
    	$order = $this->_getOrder();
    	return $order->getPayment()->getMethodInstance();
    }

    /**
     * Return order instance with loaded information by increment id
     *
     * @return Mage_Sales_Model_Order
     */
    private function _getOrder() {
        if ($this->getOrder()) {
            $order = $this->getOrder();
        } elseif ($this->_getCheckout()->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->getLastRealOrderId());
        } else {
            $order = null;
        }
        
        return $order;
    }

    /**
     * Get Form data by using ops payment api
     *
     * @return array
     */
    public function getFormFields() {
        return $this->_getPayment()->getFormFields($this->_getOrder());
    }

    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction() {
        return $this->_getPayment()->getPlatformUrl();
    }
    
    /**
     * Getting payment method code
     *
     * @return string
     */
    public function getMethodCode() {
    	return $this->_getPayment()->getCode();
    }
    
    /**
     * Shortcut fo getting config data
     *
     * @return string
     */
    public function getConfigData($name) {
    	return Mage::helper('systempay')->getConfigData($name);
    }
}