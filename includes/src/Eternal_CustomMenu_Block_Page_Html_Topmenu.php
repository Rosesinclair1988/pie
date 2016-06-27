<?php


if (!Mage::getStoreConfig('eternal_custommenu/general/enabled'))
{
    class Eternal_CustomMenu_Block_Page_Html_Topmenu extends Mage_Page_Block_Html_Topmenu
    {

    }
    return;
}

class Eternal_CustomMenu_Block_Page_Html_Topmenu extends Eternal_CustomMenu_Block_Catalog_Navigation
{

}
