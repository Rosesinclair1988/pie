<?php
class Eternal_HomeSlider_Block_Slideshow extends Mage_Core_Block_Template
{	
	public function getBlockIds()
	{
		$blockIdsString = Mage::helper('eternal_homeslider')->getConfig('general/blocks');
		$blockIds = explode(",", str_replace(" ", "", $blockIdsString));
		return $blockIds;
	}
	
	public function getProductSkus()
	{
		$productSkuString = Mage::helper('eternal_homeslider')->getConfig('general/products');
		$productSkus = explode(",", $productSkuString);
        return $productSkus;
	}
}