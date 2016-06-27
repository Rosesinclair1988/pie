<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Amazon_SynchronizationController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/configuration/synchronization');
    }

    //#############################################

    public function indexAction()
    {
        return $this->_redirect('*/adminhtml_synchronization/index');
    }

    //#############################################

    public function synchCheckAmazonProcessingNowAction()
    {
        $size = Mage::getModel('M2ePro/LockItem')->getCollection()
            ->addFieldToFilter('nick', array('like' => 'synchronization_amazon%'))
            ->getSize();

        exit((string)$size);
    }

    //#############################################
}
