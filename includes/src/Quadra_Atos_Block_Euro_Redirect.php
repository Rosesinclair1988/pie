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

class Quadra_Atos_Block_Euro_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $euro = Mage::getModel('atos/method_euro');
        $euro->callRequest();

        /* $html = '';
          $html.= $euro->getSystemMessage(); */

        /* $payment = Mage::getSingleton('checkout/session')->getQuote()->getPayment();
          if (!$card = $payment->getData('cc_type')) {
          $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
          $payment = $order->getPayment();
          $card = $payment->getData('cc_type');
          } */

        if ($euro->getSystemError()) {
            return $euro->getSystemMessage();
        }

        $html = '';

        $html.= '<style type="text/css">' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/stylesheet.css') . '");' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/checkout.css') . '");' . "\n";
        $html.= '</style>' . "\n";

        $html.= '<div id="atosButtons" style="display: none;">' . "\n";
        $html.= '  <p class="center">' . $this->__('You have to pay to validate your order') . '</p>' . "\n";
        $html.= '  <form id="atos_payment_checkout" action="' . $euro->getSystemUrl() . '" method="post">' . "\n";
        $html .= '<input type="hidden" name="1EUROCOM_x" value="1" />';
        $html .= '<input type="hidden" name="1EUROCOM_y" value="1" />';
        $html.= $euro->getSystemMessage() . "\n";
        $html.= '  </form>' . "\n";
        $html.= '</div>' . "\n";
        $html.= '<script type="text/javascript">document.getElementById("atos_payment_checkout").submit();</script>';

        return $html;
    }
}
