<?php

class MDN_GlobalPDF_Model_Template_Text extends MDN_GlobalPDF_Model_Template_Abstract {

    public function draw(&$pdf, &$page, $item, $data) {
        parent::draw($pdf, $page, $item, $data);

        //get position & size
        $position = $this->getPosition($item, $pdf, $data);
        $size = $this->getSize($item, $pdf, $data);
        $font = $this->getFont($item, $pdf, $data);
        $incrementY = $item->getAttribute('increment_y');

        //get custom datas
        $text = $item->getAttribute('value');
        $text = $this->replaceCodes($text, $data);
        $text = $this->translate($text);

        //apply modifier
        $text = $this->applyModifier($item, $text, $data);

        //set fonts settings
        //todo : font color is not applied
        $fontColor = $this->buildZendColor($font['color']);
        $page->setFillColor($fontColor);
        $page->setFont($this->getFontFromName($font['face'], $font['size']) ,$font['size']);

        //get text line height
        $position['top'] -= $font['size'];

        //draw text
        $pdf->drawTextInBlock($page, $text, $position['left'], $position['top'], $size['width'], $size['height'], $font['align']
        );

        //add offset
        if ($incrementY == "true") {
            $pdf->y -= $size['height'];
            $pdf->checkForNewPage($page);
        }
    }

}
