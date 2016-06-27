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

class Quadra_Atos_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $payment = Mage::getSingleton('checkout/session')->getQuote()->getPayment();

        if (!$card = $payment->getData('cc_type')) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            $payment = $order->getPayment();
            $card = $payment->getData('cc_type');
        }

        $standard = Mage::getModel('atos/method_standard');

        $standard->callRequest();

        if ($standard->getSystemError()) {
            return $standard->getSystemMessage();
        }

        $html = '';

        $html.= '<style type="text/css">' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/stylesheet.css') . '");' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/checkout.css') . '");' . "\n";
        $html.= '</style>' . "\n";

        $html.= '<div id="atosButtons" style="display: none;">' . "\n";
        $html.= '  <p class="center">' . $this->__('You have to pay to validate your order') . '</p>' . "\n";
        $html.= '  <form id="atos_payment_checkout" action="' . $standard->getSystemUrl() . '" method="post">' . "\n";
        $html .= '<input type="hidden" name="' . $card . '_x" value="1" />';
        $html .= '<input type="hidden" name="' . $card . '_y" value="1" />';
        $html.= $standard->getSystemMessage() . "\n";
        $html.= '  </form>' . "\n";
        $html.= '</div>' . "\n";
        $html.= '<script type="text/javascript">document.getElementById("atos_payment_checkout").submit();</script>';

        return $html;
    }
}
