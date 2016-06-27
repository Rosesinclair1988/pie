<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_IndexController extends Mage_Core_Controller_Front_Action
{
    //#############################################

    public function indexAction()
    {
        if (!Mage::getSingleton('M2ePro/Wizard')->isInstallationFinished()) {
            $this->_redirect('*/adminhtml_wizard/index');
        } else {
            $this->_redirect('*/adminhtml_about/index');
        }
    }

    //#############################################
}
