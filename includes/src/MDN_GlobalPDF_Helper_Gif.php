<?php 

class MDN_GlobalPDF_Helper_Gif extends Mage_Core_Helper_Abstract {

	/**
	 * Convert gif to jpeg (if enabled and required
	 */
	public function convertFile($filePath)
	{
		//if enabled
		if (mage::getStoreConfig('globalpdf/general/convert_gif_to_jpeg'))
		{
			if ($this->isGif($filePath))
			{
				$filePath = $this->convertGifToJpeg($filePath);
			}
		}
		
		return $filePath;
	}
	
	/**
	 * Return true if img is gif
	 */
	protected function isGif($filePath)
	{
		$t = explode(".", $filePath);
		if (count($t) > 0)
		{
			$extension = end($t);
			$extension = strtolower($extension);
			return ($extension == 'gif');
		}
		
		return false;
	}
	
	/**
	 * Convert gif to jpeg and return path
	 */
	protected function convertGifToJpeg($filePath)
	{
		$tmpPath = Mage::getBaseDir().DS.'var'.DS.'gif_to_jpeg.jpeg';
		$res = imagecreatefromgif ($filePath);
		imagejpeg($res, $tmpPath);
		return $tmpPath;
	}

}
