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

class Quadra_Atos_Block_Aurore_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $aurore = Mage::getModel('atos/method_aurore');
        $aurore->callRequest();

        if ($aurore->getSystemError()) {
            return $aurore->getSystemMessage();
        }

        $html = '';

        $html.= '<style type="text/css">' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/stylesheet.css') . '");' . "\n";
        $html.= '  @import url("' . $this->getSkinUrl('css/checkout.css') . '");' . "\n";
        $html.= '</style>' . "\n";

        $html.= '<div id="atosButtons">' . "\n";
        $html.= '  <p class="center">' . $this->__('You have to pay to validate your order') . '</p>' . "\n";
        $html.= '  <form action="' . $aurore->getSystemUrl() . '" method="post">' . "\n";
        $html.= $aurore->getSystemMessage() . "\n";
        $html.= '  </form>' . "\n";
        $html.= '</div>' . "\n";

        return $html;
    }
}
