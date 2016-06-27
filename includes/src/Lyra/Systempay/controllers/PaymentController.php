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

class Lyra_Systempay_PaymentController extends Mage_Core_Controller_Front_Action {
	/**
	 * Redirect customer to the client gateway
	 */
    public function formAction() {
    	$this->_getHelper()->log('Start');

    	// Load order
    	$lastIncrementId = $this->_getCheckout()->getLastRealOrderId();
    	
    	// Check that there is an order to pay
    	if(empty($lastIncrementId)) {
    		$this->_getHelper()->log("No order to be paid - redirecting to cart.");
    		$this->_redirect('checkout/cart');
    		return;
    	}
    		
    	$order = Mage::getModel('sales/order');
    	$order->loadByIncrementId($lastIncrementId);
    		
    	// Check that there is products in cart
    	if ($order->getTotalDue() == 0) {
    		$this->_getHelper()->log("Payment attempt with no amount - redirecting to cart.");
    		$this->_redirect('checkout/cart', array('_store'=>$order->getStore()->getId()));
    		return;
    	}
    	
    	// Check that order is not processed yet
    	if (! $this->_getCheckout()->getLastSuccessQuoteId()) {
    		$this->_getHelper()->log("Payment attempt with a quote already processed - redirecting to cart.");
    		$this->_redirect('checkout/cart', array('_store'=>$order->getStore()->getId()));
    		return;
    	}
    	
    	// Add history comment and save it
    	$order->addStatusHistoryComment($this->__("Client sent to Systempay gateway."), false)->save();
    	
    	$this->_getCheckout()->getQuote()->setIsActive(false)->save();
    	$this->_getCheckout()->setSystempayQuoteId($this->_getCheckout()->getQuoteId());
    	$this->_getCheckout()->setSystempayLastSuccessQuoteId($this->_getCheckout()->getLastSuccessQuoteId());
    	$this->_getCheckout()->clear();
    	
    	// Redirect to gateway
    	$this->_getHelper()->log('Display form and javascript');
    	
    	$this->loadLayout();
    	$this->renderLayout();
    	
    	$this->_getHelper()->log('Client ' . $order->getCustomerEmail() . ' sent to payment page for order ' . $order->getId(), Zend_Log::INFO);
		$this->_getHelper()->log('End');
    }

    /**
     * Action called after the client returns from payment gateway
     */
    public function returnAction() {
    	$this->_getHelper()->log('Start');
    	
    	$req = $this->getRequest()->getParams();

    	$this->_getCheckout()->setQuoteId($this->_getCheckout()->getSystempayQuoteId(true));
    	
    	// Loading order
    	$orderId = key_exists('vads_order_id', $req) ? $req['vads_order_id'] : 0;
    	
    	/* @var $order Mage_Sales_Model_Order */
    	$order = Mage::getModel('sales/order');
    	$order->loadByIncrementId($orderId);
    	
    	// Load API response
		$this->_getHelper()->log('Loading Lyra_Systempay_Model_Api_Response');
    	
		$systempayResp = new Lyra_Systempay_Model_Api_Response(
				$this->_getHelper()->getConfigData('ctx_mode'),
				$this->_getHelper()->getConfigData('key_test'),
				$this->_getHelper()->getConfigData('key_prod'),
				$req
		);
		
		if(!$systempayResp->isAuthentified()) {
			// authentification failed
			$this->_getHelper()->log($this->_getHelper()->getIpAddress() . " tries to access our systempay/payment/return page without valid signature. It may be a hacking attempt.",
					Zend_Log::WARN);
			$this->_redirect('checkout/onepage/failure', array('_store'=>$order->getStore()->getId()));
			return;
		}
		
		$this->_getHelper()->log('Request authenticated');
		
		// clear all messages in session
		$this->_getCheckout()->getMessages(true);
		
		// Display going to prod message
		if($this->_getHelper()->getConfigData('ctx_mode') == 'TEST') {
			$message = $this->__("<p><u>GOING INTO PRODUCTION</u></p>You want to know how to put your shop into production mode, please go to this URL : ");
			$message .= '<a href="https://paiement.systempay.fr/html/faq/prod" target="_blank">https://paiement.systempay.fr/html/faq/prod</a>';
			$this->_getCheckout()->addNotice($message);
		}
		
		$this->_getCheckout()->setLastSuccessQuoteId($this->_getCheckout()->getSystempayLastSuccessQuoteId(true));

		if($order->getStatus() == 'pending_payment') {
			$this->_getHelper()->log('Order ' . $order->getId() . ' is waiting payment');
			
			// Order waiting for payment
			if ($systempayResp->isAcceptedPayment()) {
				$this->_getHelper()->log('Payment for order '.$order->getId().' has been confirmed by client return ! This means the check URL did not work.', Zend_Log::WARN);
				
				// Save order and create invoice
				$this->registerOrder($order, $systempayResp);

				// Display success page
				if($this->_getHelper()->getConfigData('ctx_mode') == 'TEST') {
					// order not paid => not validated from check url ; ctx_mode=TEST => user is webmaster
					// So log and display a warning about check url not working
					$message = $this->__("The automatic notification (peer to peer connection between the payment platform and your shopping cart solution) hasn't worked. Have you correctly set up the server URL in your Systempay backoffice ?");
					$message .= '<br /><br />';
					$message .= $this->__("For understanding the problem, please read the documentation of the module : <br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;To read carefully before going further&raquo;<br />&nbsp;&nbsp;&nbsp;- Chapter &laquo;Server URL settings&raquo;");
					$this->_getCheckout()->addError($message);
				}
				
				$this->_getHelper()->log('Redirecting to success page');
				$this->_redirect('checkout/onepage/success', array('_store'=>$order->getStore()->getId()));
			} else {
				// Client returns with a canceled/refused payment, send him back to checkout
				$this->_getHelper()->log('Payment for order '.$order->getId().' has failed.', Zend_Log::INFO);
				
				// Cancel Order and refill cart
				$this->manageRefusedPayment($order, $systempayResp);
				
				$this->_getHelper()->log('Redirecting to cart page');
				$this->_getCheckout()->addWarning($this->__('Checkout and order have been canceled.'));
				$this->_redirect('checkout/cart', array('_store'=>$order->getStore()->getId()));
			}
		} else {
			// Payment already registered
			$this->_getHelper()->log('Order '.$order->getId().' has already been registered.');

			if($systempayResp->isAcceptedPayment()) {
				$this->_getHelper()->log('Order '.$order->getId().' is reconfirmed');
				$this->_redirect('checkout/onepage/success', array('_store'=>$order->getStore()->getId()));
			} elseif($order->isCanceled()) {
				$this->_getHelper()->log('Order '.$order->getId().' cancelation is reconfirmed');
				$this->_getCheckout()->addWarning($this->__('Checkout and order have been canceled.'));
				$this->_redirect('checkout/cart', array('_store'=>$order->getStore()->getId()));
			} else {
				// This is an error case, the client returns with an error code but the payment already has been accepted
				$this->_getHelper()->log('Order '.$order->getId().' has been validated but we receive a payment error code !',
						Zend_Log::ERR);
				$this->_redirect('checkout/onepage/failure', array('_store'=>$order->getStore()->getId()));
			}
		}
		
		$this->_getHelper()->log('End');
    }


    /**
     * Action called by the payment gateway to confirm (or not) a payment
     */
    public function checkAction() {
    	$this->_getHelper()->log('Start');
    	
    	$req = $this->getRequest()->getParams();
    	
		$this->_getCheckout()->setQuoteId($this->_getCheckout()->getSystempayQuoteId(true));
		
		// Loading order
		$orderId = key_exists('vads_order_id', $req) ? $req['vads_order_id'] : 0;
		
		/* @var $order Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		
		// Get store id from order
		$storeId = $order->getStore()->getId();
		
    	// Load API response 
		$this->_getHelper()->log('Loading Lyra_Systempay_Model_Api_Response');
		
		$systempayResp = new Lyra_Systempay_Model_Api_Response(
				$this->_getHelper()->getConfigData('ctx_mode', $storeId),
				$this->_getHelper()->getConfigData('key_test', $storeId),
				$this->_getHelper()->getConfigData('key_prod', $storeId),
				$req
		);

		if(! $systempayResp->isAuthentified()) {
			// authentification failed
			$this->_getHelper()->log($this->_getHelper()->getIpAddress() . " tries to access our systempay/payment/check page without valid signature. It may be a hacking attempt.",
					Zend_Log::WARN);
        	$message = $systempayResp->getOutputForGateway('auth_fail');
        	$this->getResponse()->setBody($message);
			return;
		}
		
		$this->_getHelper()->log('Request authenticated');

		if($order->getStatus() == 'pending_payment') {
			$this->_getHelper()->log('Order '.$order->getId().' is waiting payment');
			
			// Order waiting for payment
			if($systempayResp->isAcceptedPayment()) {
				$this->_getHelper()->log('Payment for order '.$order->getId().' has been confirmed by check URL', Zend_Log::INFO);
				
				// Save order and create invoice
				$this->registerOrder($order, $systempayResp);

				// Display check url confirmation message
				$message = $systempayResp->getOutputForGateway('payment_ok');
        		$this->getResponse()->setBody($message);
			} else {
				// Gateway refused the payment
				$this->_getHelper()->log('Payment for order '.$order->getId().' has been invalidated by check URL', Zend_Log::INFO);
				
				// Manage payment failure
				$this->manageRefusedPayment($order, $systempayResp);
				
				// Display check url failure message 
				$message = $systempayResp->getOutputForGateway('payment_ko');
				$this->getResponse()->setBody($message);
			}
		} else {
			// Payment already registered
			if($systempayResp->isAcceptedPayment()) {
				$this->_getHelper()->log('Order '.$order->getId().' is reconfirmed');
				$message = $systempayResp->getOutputForGateway('payment_ok_already_done');
        		$this->getResponse()->setBody($message);
			} elseif($order->isCanceled()) {
				$this->_getHelper()->log('Order '.$order->getId().' cancelation is reconfirmed');
				$message = $systempayResp->getOutputForGateway('payment_ko');
				$this->getResponse()->setBody($message);
			} else {
				// This is an error case, the client returns with an error code but the payment already has been accepted
				$this->_getHelper()->log('Order '.$order->getId().' has been validated but we receive a payment error code !',
						Zend_Log::ERR);
				$message = $systempayResp->getOutputForGateway('payment_ko_on_order_ok');
				$this->getResponse()->setBody($message);
			}
		}

		$this->_getHelper()->log('End');
    }

    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @param    Lyra_Systempay_Model_Api_Response $response
     */
    private function registerOrder(Mage_Sales_Model_Order $order, Lyra_Systempay_Model_Api_Response $response) {
    	$this->_getHelper()->log('Start');
    	
    	// Save Platform responses
    	$this->_setTransactionInfo($order, $response);
    	
    	$this->_getHelper()->log('Retrieving statuses configuration');
    	
    	$newStatus = $this->_getHelper()->getConfigData('registered_order_status', $order->getStore()->getId());
    	$processingStatuses = Mage::getModel('sales/order_config')->getStateStatuses(Mage_Sales_Model_Order::STATE_PROCESSING);

    	$this->_getHelper()->log('Capturing payment for order ' . $order->getId());
    	    	
        if (array_key_exists($newStatus, $processingStatuses)) {
			$autoCapture = $this->_getHelper()->getConfigData('capture_auto', $order->getStore()->getId());
			if($order->canInvoice() && $autoCapture) { /* Creating invoice allowed */
				$this->_getHelper()->log('Creating invoice for order ' . $order->getId());
				
				// convert order to invoice
				$invoice = $order->prepareInvoice();
				$invoice->register()->capture();
				$order->addRelatedObject($invoice);
				
				$message = $this->__('Invoice %s was created', $invoice->getIncrementId());
			} else {
				$message = $this->__('Order registered');
			}
			
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $newStatus, $message);
        } else {
			$order->setState(Mage_Sales_Model_Order::STATE_NEW, $newStatus, $this->__('Order registered'));
        }

        // Add history entry
        $order->addStatusHistoryComment($response->getLogString());

		$this->_getHelper()->log('Saving confirmed order and sending email');
		
		$order->save();
		$order->sendNewOrderEmail();

		$this->_getHelper()->log('End');
    }


    /**
     * Cancel order 
     * @param Mage_Sales_Model_Order $order
     * @param Lyra_Systempay_Model_Api_Response $response
     */
    private function manageRefusedPayment(Mage_Sales_Model_Order $order, Lyra_Systempay_Model_Api_Response $response) {
    	$this->_getHelper()->log('Start');
    	$this->_getHelper()->log('Canceling order '.$order->getId(), Zend_Log::INFO);
    	
    	// Save Platform responses
    	$this->_setTransactionInfo($order, $response);
    	
    	if($this->_getHelper()->getConfigData('refill_cart', $order->getStore()->getId())) {
			// Re-fill the cart so that the client can reorder quicker
			$cart = Mage::getSingleton('checkout/cart');
			$items = $order->getItemsCollection();
			foreach ($items as $item) {
	            try {
	                $cart->addOrderItem($item,$item->getQty());
	            } catch (Mage_Core_Exception $e){
	                if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
	                    Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
	                } else {
	                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
	                }
	            } catch (Exception $e) {
	                Mage::getSingleton('checkout/session')->addException($e,
	                    Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
	                );
	            }
	        }
	        
	        // Associate cart with order customer
	        $customer = Mage::getModel('customer/customer');
	        $customer->load($order->getCustomerId());
	        $cart->getQuote()->setCustomer($customer);
	        $cart->save();
    	}
    	
    	$order->cancel();
    	
    	// Add history entry
    	$order->addStatusHistoryComment($response->getLogString());
    	
    	$order->save();
    	
    	/* @var $session Mage_Checkout_Model_Session */
    	$this->_getHelper()->log('Unsetting order data in session');
    	$this->_getCheckout()->unsLastQuoteId()
    			->unsLastSuccessQuoteId()
    			->unsLastOrderId()
    			->unsLastRealOrderId();
    	
		$this->_getHelper()->log('End');
    }
    
    private function _setTransactionInfo(Mage_Sales_Model_Order $order, Lyra_Systempay_Model_Api_Response $response) {
    	
    	// Set common payment information
    	$order->getPayment()
		    	->setCcTransId($response->get('trans_id'))
		    	->setCcType($response->get('card_brand'))
		    	->setCcStatus($response->getCode())
		    	->setCcStatusDescription($response->getMessage());
    	
    	if($response->get('card_brand') == 'MULTI') {
    		// save payment infos to sales_flat_order_payment

    		$currency = $this->_getPaymentHelper()->findCurrencyByNumCode($response->get('currency'));
    		
    		$data = json_decode($response->get('payment_seq'));
    		$transactions = $data->{'transactions'};
    		
    		if(! $response->isCancelledPayment()) {
    			// save transactions' details to sales_payment_transaction
    			
    			foreach($transactions as $trs) {
    				if (!empty($trs->{'expiry_month'}) && !empty($trs->{'expiry_year'})) {
    					$expiry = str_pad($trs->{'expiry_month'}, 2, '0', STR_PAD_LEFT) . '/' . $trs->{'expiry_year'};
    				} else {
    					$expiry = '-';
    				}
    				
    				$order->getPayment()
		    				->setSkipTransactionCreation(false)
		    				->setTransactionId($response->get('trans_id') . '-' . $trs->{'sequence_number'})
		    				->setTransactionAdditionalInfo(
		    						'raw_details_info',
		    						array(
		    								'Amount Paid' => $currency->convertAmountToFloat($trs->{'amount'}) . ' ' . $currency->getAlpha3(),
		    								'Transaction Status' => $trs->{'trans_status'},
		    								'Extra Transaction ID' => property_exists($trs, 'ext_trans_id') && isset($trs->{'ext_trans_id'}) ? $trs->{'ext_trans_id'} : '-',
		    								'Credit Card Number' => $trs->{'card_number'},
		    								'Expiration Date' => $expiry,
		    								'Payment Mean' => $trs->{'card_brand'}
		    						));
    			
    				$trsType = $response->isAcceptedPayment() ?
		    				Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE :
		    				Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
    			
    				$order->getPayment()->addTransaction($trsType, null, true, $this->__('Payment transaction created.'));
    			}
    		}
    	} else {
    		// save payment infos to sales_flat_order_payment
    		$order->getPayment()
		    		->setCcExpMonth($response->get('expiry_month'))
		    		->setCcExpYear($response->get('expiry_year'))
		    		->setCcNumberEnc($response->get('card_number'));
    		
    		if(! $response->isCancelledPayment()) {
	    		// save transaction details to sales_payment_transaction
	    		$order->getPayment()
	    				->setSkipTransactionCreation(false)
	    				->setTransactionId($response->get('trans_id'))
	    				->setTransactionAdditionalInfo(
		    				'raw_details_info',
		    				array(
		    						'Message' => $response->getMessage() . '(' . $response->getCode() . ')',
		    						'Transaction ID' => $response->get('trans_id'),
		    						'Credit Card Number' => $response->get('card_number'),
		    						'Expiration Date' => str_pad($response->get('expiry_month'), 2, '0', STR_PAD_LEFT) . ' / ' . $response->get('expiry_year'),
		    						'Payment Mean' => $response->get('card_brand')
		    				));
    		    		
    			$trsType = $response->isAcceptedPayment() ?
		    			Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE :
		    			Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
    		
    			$order->getPayment()->addTransaction($trsType, null, true, $this->__('Payment transaction created.'));
    		}
    	}
    }
    
    /**
	 * Return systempay data helper.
	 * 
	 * @return Lyra_Systempay_Helper_Data
	 */
    private function _getHelper() {
    	return Mage::helper('systempay');
    }
    
    /**
     * Return systempay payment method helper.
     * 
     * @return Mage_Systempay_Helper_Payment
     */
    private function _getPaymentHelper() {
    	return Mage::helper('systempay/payment');
    }
    
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckout() {
    	return Mage::getSingleton('checkout/session');
    }
    
}