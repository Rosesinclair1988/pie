<?php
class Eternal_HomeSlider_Block_Banner extends Mage_Core_Block_Template
{	
	public function getBannerIds()
	{
		$blockIdsString = Mage::helper('eternal_homeslider')->getConfig('banner/blocks');
		$blockIds = explode(",", str_replace(" ", "", $blockIdsString));
		return $blockIds;
	}
    
    public function getBannerCount()
    {
        return Mage::helper('eternal_homeslider')->getConfig('banner/count');
    }
}