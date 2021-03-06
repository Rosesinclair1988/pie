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

class Quadra_Atos_AuroreController extends Mage_Core_Controller_Front_Action
{

    protected $_atosAuroreResponse = null;
    protected $_realOrderIds;
    protected $_quote;

    /**
     * Get singleton with Atos aurore
     *
     * @return object Quadra_Atos_Model_Aurore
     */
    public function getAurore()
    {
        return Mage::getSingleton('atos/method_aurore');
    }

    public function getConfig()
    {
        return Mage::getSingleton('atos/config');
    }

    /**
     * Get quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (!$this->_quote) {
            $session = Mage::getSingleton('checkout/session');
            $this->_quote = Mage::getModel('sales/quote')->load($session->getAtosAuroreQuoteId());

            if (!$this->_quote->getId()) {
                $realOrderIds = $this->getRealOrderIds();
                if (count($realOrderIds)) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderIds[0]);
                    $this->_quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                }
            }
        }
        return $this->_quote;
    }

    /**
     * Get real order ids
     *
     * @return array
     */
    public function getRealOrderIds()
    {
        if (!$this->_realOrderIds) {
            if ($this->_atosAuroreResponse) {
                $this->_realOrderIds = explode(',', $this->_atosAuroreResponse['order_id']);
            } else {
                return array();
            }
        }
        return $this->_realOrderIds;
    }

    /**
     * seting response after returning from atos
     *
     * @param array $response
     * @return object $this
     */
    protected function setAtosResponse($response)
    {
        if (count($response)) {
            $this->_atosAuroreResponse = $response;
        }
        return $this;
    }

    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');

        if ($session->getQuote()->getHasError()) {
            $this->_redirect('checkout/cart');
        } else {
            if ($session->getQuoteId()) {
                $session->setAtosAuroreQuoteId($session->getQuoteId());
            }

            $this->getResponse()->setBody($this->getLayout()->createBlock('atos/aurore_redirect')->toHtml());
        }
    }

    public function cancelAction()
    {
        $model = $this->getAurore();
        $session = Mage::getSingleton('checkout/session');

        if ($response = $model->getApiResponse()->doResponse($_REQUEST['DATA'], array('bin_response' => $model->getBinResponse()))) {
            $this->setAtosResponse($response);
            Mage::getModel('atos/log_response')->logResponse('cancel', $response);

            foreach ($this->getRealOrderIds() as $realOrderId) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);

                if ($order->getId()) {
                    $order->cancel();
                    $order->save();
                }
            }
        }

        if (!$model->getConfigData('empty_cart')) {
            $this->_reorder();
        } else {
            $session->setQuoteId($session->getAtosAuroreQuoteId(true));
        }

        $session->setCanRedirect(false);
        $session->addNotice($this->__('The payment was canceled.'));

        $this->_redirect('checkout/cart');
    }

    public function normalAction()
    {
        $model = $this->getAurore();
        $session = Mage::getSingleton('checkout/session');

        if (!$this->getRequest()->isPost('DATA')) {
            $this->_redirect('');
            return;
        }

        $response = $model->getApiResponse()->doResponse($_REQUEST['DATA'], array('bin_response' => $model->getBinResponse()));
        $this->setAtosResponse($response);
        Mage::getModel('atos/log_response')->logResponse('normal', $response);

        if ($response['merchant_id'] != $model->getMerchantId()) {
            Mage::log(sprintf('Response Merchant ID (%s) is not valid with configuration value (%s)' . "\n", $response['merchant_id'], $model->getMerchantId()), null, 'atos');

            $session->addError($this->__('We are sorry but we have an error with payment module'));
            $this->_redirect('checkout/cart');
            return;
        }

        switch ($response['response_code']) {
            case '00':
                $session->setQuoteId($session->getAtosAuroreQuoteId(true));
                $session->getQuote()->setIsActive(false)->save();

                if ($this->getQuote()->getIsMultiShipping()) {
                    foreach ($this->getRealOrderIds() as $realOrderId) {
                        $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
                        $orderIds[$order->getId()] = $realOrderId;
                    }

                    Mage::getSingleton('checkout/type_multishipping')
                        ->getCheckoutSession()
                        ->setDisplaySuccess(true);

                    $session->setCanRedirect(false);

                    Mage::getSingleton('core/session')->setOrderIds($orderIds);
                }

                $this->_redirect($this->_getSuccessRedirect(), array('_secure' => true));
                break;
            default:
                $session->addError($this->__('(Response Code %s) Error with payment module', $response['response_code']));
                $this->_redirect('checkout/cart');
                break;
        }
    }


    public function automaticAction()
    {
        $model = $this->getAurore();

        if (!$this->getRequest()->isPost('DATA')) {
            $this->_redirect('');
            return;
        }

        if ($this->getConfig()->getCheckByIpAddress()) {
            if (!in_array($this->getConfig()->getIpAddress(), $this->getConfig()->getAuthorizedIps())) {
                Mage::log($this->getConfig()->getIpAddress() . ' tries to connect to our server' . "\n", null, 'atos');
                return;
            }
        }

        $response = $model->getApiResponse()->doResponse($_REQUEST['DATA'], array('bin_response' => $model->getBinResponse()));
        $this->setAtosResponse($response);
        Mage::getModel('atos/log_response')->logResponse('automatic', $response);

        if ($response['merchant_id'] != $model->getMerchantId()) {
            Mage::log(sprintf('Response Merchant ID (%s) is not valid with configuration value (%s)' . "\n", $response['merchant_id'], $model->getMerchantId()), null, 'atos');
            return;
        }

        foreach ($this->getRealOrderIds() as $realOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
            $this->_updateOrderState($order, $response, $model);
        }
    }

    protected function _updateOrderState($order, $response, $model) {
        switch ($response['response_code']) {
            // Success order
            case '00':
                if ($order->getId()) {
                    $order->addStatusToHistory(
                        Mage_Sales_Model_Order::STATE_PROCESSING, Mage::getSingleton('atos/api_response')->describeResponse($response)
                    );

                    // Create invoice
                    if ($model->getConfigData('invoice_create')) {
                        $this->saveInvoice($order);
                    }

                    if (!$order->getEmailSent()) {
                        $order->sendNewOrderEmail();
                    }
                }
                break;

            default:
                // Cancel order
                if ($order->getId()) {
                    $order->addStatusToHistory(
                        Mage_Sales_Model_Order::STATE_CANCELED, Mage::getSingleton('atos/api_response')->describeResponse($response)
                    );

                    $order->cancel();
                }
                break;
        }

        $order->save();
    }

    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @return	  boolean Can save invoice or not
     */
    protected function saveInvoice(Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            $convertor = Mage::getModel('sales/convert_order');

            $invoice = $convertor->toInvoice($order);

            foreach ($order->getAllItems() as $orderItem) {
                $qty = $orderItem->isDummy() ? 1 : $orderItem->getQtyToInvoice();

                if (!$qty) {
                    continue;
                }

                $item = $convertor->itemToInvoiceItem($orderItem);
                $item->setQty($qty);
                $invoice->addItem($item);
            }

            $invoice->collectTotals();
            $invoice->register();

            try {
                Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('atos')->__('Invoice %s was created', $invoice->getIncrementId()));

                $invoice->sendEmail();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('atos')->__('Invoice %s was send', $invoice->getIncrementId()));

                if (!$invoice->getEmailSent())
                    $invoice->setData('email_sent', '1')
                            ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return false;
    }

    protected function _reorder()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $cartTruncated = false;
        /* @var $cart Mage_Checkout_Model_Cart */

        foreach ($this->getRealOrderIds() as $realOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);

            if ($order->getId()) {
                $items = $order->getItemsCollection();
                foreach ($items as $item) {
                    try {
                        $cart->addOrderItem($item);
                    } catch (Mage_Core_Exception $e){
                        if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                            Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                        }
                        else {
                            Mage::getSingleton('checkout/session')->addError($e->getMessage());
                        }
                    } catch (Exception $e) {
                        Mage::getSingleton('checkout/session')->addException($e,
                            Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
                        );
                    }
                }
            }
        }

        $cart->save();
    }

    protected function _getSuccessRedirect()
    {
        if ($this->getQuote()->getIsMultiShipping())
            return 'checkout/multishipping/success';
        else
            return 'checkout/onepage/success';
    }
}
