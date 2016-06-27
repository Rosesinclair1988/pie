<?php

class Eternal_CustomMenu_Block_Toggle extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        if (!Mage::getStoreConfig('eternal_custommenu/general/enabled')) return;
        $layout = $this->getLayout();
        $topnav = $layout->getBlock('catalog.topnav');
        if (is_object($topnav))
        {
            $topnav->setTemplate('eternal/custommenu/top.phtml');
            $head = $layout->getBlock('head');
            $head->addJs('eternal/custommenu.js');
            $head->addItem('skin_css', 'css/eternal/custommenu.css');
        }
    }
}
