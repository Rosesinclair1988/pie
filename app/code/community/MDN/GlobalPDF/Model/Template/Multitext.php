<?php

class MDN_GlobalPDF_Model_Template_Multitext extends MDN_GlobalPDF_Model_Template_Abstract {

    public function draw(&$pdf, &$page, $item, $data) {
        parent::draw($pdf, $page, $item, $data);

        //get position & size
        $position = $this->getPosition($item, $pdf, $data);
        $size = $this->getSize($item, $pdf, $data);
        $font = $this->getFont($item, $pdf, $data);
        $padding = $this->getPadding($item, $pdf);

        //get custom datas
        $maxHeight = $item->getAttribute('max_height');
        $storeOffset = $item->getAttribute('store_offset');
        $text = $item->getAttribute('value');
        $lineHeight = $item->getAttribute('line_height');
        $maxLines = $item->getAttribute('max_lines');
        $enableNewPage = ($item->getAttribute('enable_new_page') == 1);
        $incrementY = $item->getAttribute('increment_y');
        if ($lineHeight == '')
            $lineHeight = $font['size'] + 3;

        //set fonts settings
        $fontColor = $this->buildZendColor($font['color']);
        $page->setFillColor($fontColor);       
        $page->setFont($this->getFontFromName($font['face'], $font['size']) ,$font['size']);

        //format text according to width
        $text = $item->getAttribute('value');
        $text = preg_replace('#</br>#',"\n", $text);
        $text = $this->replaceCodes($text, $data);
        $text = $this->translate($text);

        //get text line height
        $position['top'] -= $lineHeight;

        //draw text
        $offset = $pdf->DrawMultilineText($page,
                        $text,
                        $position['left'],
                        $position['top'],
                        $size['width'],
                        $lineHeight,
                        $maxLines,
                        $enableNewPage,
                        $maxHeight,
						$padding
        );

		//store offset in variable if required
		if ($storeOffset)
			mage::helper('GlobalPDF')->addCustomData($storeOffset, $offset);

        //add offset
        if ($incrementY == "true") {
            $pdf->y -= $offset;
            //echo '<p>'.$offset.'</p>';
        }
    }

}
