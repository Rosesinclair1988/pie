<?php
/**
 * 1997-2012 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author Quadra Informatique <ecommerce@quadra-informatique.fr>
 *  @copyright 1997-2012 Quadra Informatique
 *  @version Release: $Revision: 2.0.6 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Quadra_Atos_Model_Method_Aurore extends Quadra_Atos_Model_Abstract
{
    private $_url = null;
    private $_message = null;
    private $_error = false;
    protected $_code = 'atos_aurore';
    protected $_formBlockType = 'atos/aurore_form';
    protected $_infoBlockType = 'atos/aurore_info';

    /*
     * Availability options
     */
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;

    public function getCode()
    {
        return $this->_code;
    }

    public function isAvailable($quote = null)
    {
        /*if (Mage::getSingleton('checkout/session')->getQuote()->getIsMultiShipping()) {
            return false;
        } else {
            return parent::isAvailable($quote);
        }*/
        return parent::isAvailable($quote);
    }

    /**
     *  Form block description
     *
     *  @return	 object
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock($this->_formBlockType, $name);
        $block->setMethod($this->_code);
        $block->setPayment($this->getPayment());
        return $block;
    }

    /**
     *  @return	  string Return cancel URL
     */
    public function getCancelReturnUrl()
    {
        return Mage::getUrl('atos/aurore/cancel');
    }

    /**
     *  Return URL for customer response
     *
     *  @return	  string Return customer URL
     */
    public function getNormalReturnUrl()
    {
        return Mage::getUrl('atos/aurore/normal');
    }

    /**
     *  Return URL for automatic response
     *
     *  @return	  string Return automatic URL
     */
    public function getAutomaticReturnUrl()
    {
        return Mage::getUrl('atos/aurore/automatic');
    }

    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        Mage::getSingleton('checkout/session')->setIsMultishipping(false);

        return Mage::getUrl('atos/aurore/redirect');
    }

    public function callRequest()
    {
        $parameters = array(
            'bin_request' => $this->getBinRequest(),
            'merchant_id' => $this->getMerchantId(),
            'payment_means' => 'AURORE,2',
            'url' => array(
                'cancel' => $this->getCancelReturnUrl(),
                'normal' => $this->getNormalReturnUrl(),
                'automatic' => $this->getAutomaticReturnUrl()
            )
        );

        $sips = $this->getApiRequest()->doRequest($parameters);

        if (($sips['code'] == "") && ($sips['error'] == "")) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__('Call Bin Request Error - Check path to the file or command line for debug');
        } elseif ($sips['code'] != 0) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__($sips['error']);
        } else {
            $regs = array();

            if (preg_match('/<form [^>]*action="([^"]*)"[^>]*>(.*)<\/form>/i', $sips['message'], $regs)) {
                $this->_url = $regs[1];
                $this->_message = $regs[2];
            } else {
                $this->_error = true;
                $this->_message = 'Call Bin Request Error - Check path to the file or command line for debug';
            }
        }
    }

    public function getSystemUrl()
    {
        return $this->_url;
    }

    public function getSystemMessage()
    {
        return $this->_message;
    }

    public function getSystemError()
    {
        return $this->_error;
    }

    /**
     * Return merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getConfigData('merchant_id');
    }

    public function getPathfile()
    {
        return $this->getConfigData('pathfile');
    }

    /**
     *  Return Atos bin file for request
     *
     *  @return	  string
     */
    public function getBinRequest()
    {
        return $this->getConfigData('bin_request');
    }

    /**
     *  Return Atos bin file for response
     *
     *  @return	  string
     */
    public function getBinResponse()
    {
        return $this->getConfigData('bin_response');
    }


    public function getCheckByIpAddress()
    {
        return $this->getConfigData('check_ip_address');
    }

    /**
     *  Return new order status
     *
     *  @return	  string New order status
     */
    public function getNewOrderStatus()
    {
        return $this->getConfigData('order_status');
    }
}
