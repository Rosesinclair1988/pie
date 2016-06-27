<?php 

class MDN_GlobalPDF_Helper_Category extends Mage_Core_Helper_Abstract {

	/**
	 * Return product ids for given category id
	 **/
	public function getProductIds($categoryId)
	{
		$category = mage::getModel('catalog/category')->load($categoryId);
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addCategoryFilter($category)
			->getAllIds();
				
		return $collection;
	}
	

}
