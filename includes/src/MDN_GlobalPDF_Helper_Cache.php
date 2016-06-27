<?php 

class MDN_GlobalPDF_Helper_Cache extends Mage_Core_Helper_Abstract {

	/**
	 * Clear cache
	 */
	public function clear()
	{
		
	}
	
	/**
	 * Return product file path
	 */
	public function getProductFilePath($productId)
	{
		return mage::helper('GlobalPDF')->getCacheDirectory().$productId.'.pdf';
	}
	
	/**
	 * Return file content
	 */
	public function getFileContent($path)
	{
		$handle = fopen($path, "r");
		$contents = fread($handle, filesize($path));
		fclose($handle);
		return $contents;	
	}

}
