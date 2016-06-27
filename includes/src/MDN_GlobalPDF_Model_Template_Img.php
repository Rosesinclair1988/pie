<?php

class MDN_GlobalPDF_Model_Template_Img extends MDN_GlobalPDF_Model_Template_Abstract
{

	public function draw(&$pdf, &$page, $item, $data)
	{
		parent::draw($pdf, $page, $item, $data);

		//get position & size
		$position = $this->getPosition($item, $pdf, $data);
		$size = $this->getSize($item, $pdf, $data);

        //get image path & mode
		$imagePath = $item->getAttribute('src');
		$imageMode = $item->getAttribute('mode');
		$imagePath = $this->replaceCodes($imagePath, $data);

		//todo : return default magento picture
		if (!file_exists($imagePath))
		{
			if (mage::getStoreConfig('globalpdf/general/debug_mode'))
				die($imagePath.' does not exist !');
			$imagePath = mage::helper('GlobalPDF')->getMissingImagePath();
		}
		
		//apply mode
		switch($imageMode)
		{
			case 'zoom':
			    @$imageSize = getimagesize($imagePath);
				$imageWidth = $imageSize[0];
				$imageHeight = $imageSize[1];
				
				//deduct coef
				$widthCoef = $size['width'] / $imageWidth;
				$heightCoef = $size['height'] / $imageHeight;
				$coef = ($widthCoef < $heightCoef ? $widthCoef : $heightCoef);
				
				//apply coef
				$oldWidth = $size['width'];
				$oldHeight = $size['height'];
				$size['width'] = $imageWidth * $coef;
				$size['height'] = $imageHeight * $coef;

				//change top & left to center image
				$position['left'] += ($oldWidth - $size['width']) / 2;
				$position['top'] -= ($oldHeight - $size['height']) / 2;
				
				break;
			default:
				//nothing, shrink picture
				break;
		}
		
		//draw image
		try
		{
			//if convert gif to jpeg is enabled
			$imagePath = mage::helper('GlobalPDF/Gif')->convertFile($imagePath);
		
			$pdf->drawImage($page, $imagePath, $position['left'], $position['top'], $size['width'], $size['height']);		
		}
		catch(Exception $ex)
		{
			//raise exception only if debug mode is enabled
			if (mage::getStoreConfig('globalpdf/general/debug_mode'))
				throw new Exception($ex);
		}
			
	}

}
