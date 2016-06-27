<?php

abstract class MDN_GlobalPDF_Model_Template_Abstract extends Mage_Core_Model_Abstract {

    public $template = null;
    protected $_pdf = null;

    /**
     *
     * @param type $pdf =>  object(MDN_GlobalPDF_Model_Pdf_Order)
     * @param type $page => object(Zend_Pdf_Page)
     * @param type $item => object(DOMElement)
     * @param type $data => array
     */
    public function draw(&$pdf, &$page, $item, $data) {

        $this->_pdf = $pdf;

/*
        echo'<pre>'; 
        var_dump($item);
        echo'</pre>';
*/
        
        //Draw debug border if enabled
        if (mage::getStoreConfig('globalpdf/general/debug_mode')) {
            $this->drawDebug($pdf, $page, $item, $data);
        }
        
        //draw background
        if ($item->getAttribute('bgcolor')) {
            $this->drawBackground($pdf, $page, $item, $data);
        }

        //Draw border if enabled
        if ($item->getAttribute('border') == 1) {
            $this->drawBorder($pdf, $page, $item, $data);
        }
    }

    /**
     * Return position data from node
     */
    public function getPosition($item, &$pdf, $data) {
        $position = array();

        //apply default settings
        $position['left'] = 0;
        $position['top'] = 0;
        $position['mode'] = 'relative';

        $positionNodes = $item->getElementsByTagName('position');
        if ($positionNode = $positionNodes->item(0)) {
            $position['left'] = $this->replaceCodes($positionNode->getAttribute('left'), $data);
            $position['top'] = $this->replaceCodes($positionNode->getAttribute('top'), $data);
            $position['mode'] = $positionNode->getAttribute('mode');
        }

        //convert top according to mode
        switch ($position['mode']) {
            case 'relative':
                $position['top'] = $pdf->y - $position['top'];
                $position['left'] = $pdf->x + $position['left'];
                break;
            case 'absolute':
                $position['top'] = $pdf->_PAGE_HEIGHT - $position['top'];
                break;
        }

        return $position;
    }

    /**
     * Return size data from node
     */
    public function getSize($item, &$pdf, $data) {
        $size = array();

        //apply default settings
        $size['width'] = 0;
        $size['height'] = 0;

        $sizeNodes = $item->getElementsByTagName('size');
        if ($sizeNode = $sizeNodes->item(0)) {
            $size['width'] = $this->replaceCodes($sizeNode->getAttribute('width'), $data);
            $size['height'] = $this->replaceCodes($sizeNode->getAttribute('height'), $data);
        }

        return $size;
    }

    /**
     * Return padding data from node
     */
    public function getPadding($item, &$pdf) {
        $padding = array();

        //apply default settings
        $padding['top'] = 0;
        $padding['left'] = 0;
        $padding['right'] = 0;
        $padding['bottom'] = 0;

        $paddingNodes = $item->getElementsByTagName('padding');
        if ($paddingNode = $paddingNodes->item(0)) {
            $padding['top'] = $paddingNode->getAttribute('top');
            $padding['left'] = $paddingNode->getAttribute('left');
            $padding['right'] = $paddingNode->getAttribute('right');
            $padding['bottom'] = $paddingNode->getAttribute('bottom');
        }

        return $padding;
    }

    /**
     * Return font datas from node
     */
    public function getFont($item, &$pdf, $data) {
        $font = array();

        //apply default settings
        $font['face'] = 'Helvetica';
        $font['size'] = 10;
        $font['style'] = '';
        $font['color'] = '0,0,0'; // black
        $font['align'] = 'left';

        $fontNodes = $item->getElementsByTagName('font');
  
        
        if ($fontNode = $fontNodes->item(0)) {
            $font['face'] = $this->replaceCodes($fontNode->getAttribute('face'), $data);
            $font['size'] = $this->replaceCodes($fontNode->getAttribute('size'), $data);
            $font['style'] = $this->replaceCodes($fontNode->getAttribute('style'), $data);
            $font['color'] = $this->replaceCodes($fontNode->getAttribute('color'), $data);
            $font['align'] = $this->replaceCodes($fontNode->getAttribute('align'), $data);
        }

        //convert align code
        if ($font['align'] == 'center')
            $font['align'] = 'c';
        if ($font['align'] == 'left')
            $font['align'] = 'l';
        if ($font['align'] == 'right')
            $font['align'] = 'r';

      // info log
      //Mage::log(sprintf("get Font %s", print_r($font, true)), null, 'Abstract_getFont.log');
        
        return $font;
    }

    /**
     * Build zend color from a R,G,B string
     */
    public function buildZendColor($colorsValue) {
        $t = explode(',', $colorsValue);
        if (count($t) == 3) {
            $c = new Zend_Pdf_Color_Rgb($t[0] / 256, $t[1] / 256, $t[2] / 256);
        } else {
            $c = new Zend_Pdf_Color_Rgb(0, 0, 0);
        }
        return $c;
    }

    /**
     * Draw item border
     */
    protected function drawBorder(&$pdf, &$page, $item, $data) {

        //get position & size
        $position = $this->getPosition($item, $pdf, $data);
        $size = $this->getSize($item, $pdf, $data);
        $borderColor = $item->getAttribute('bordercolor');

        //set border color
        $color = $this->buildZendColor($borderColor);
        $page->setLineColor($color);

        if ((!is_numeric($position['left']))
                || (!is_numeric($position['top']))
                || (!is_numeric($size['height']))
                || (!is_numeric($size['width'])))
            return false;

        //Draw background
        $page->drawRectangle($position['left'], $position['top'], $position['left'] + $size['width'], $position['top'] - $size['height'], Zend_Pdf_Page::SHAPE_DRAW_STROKE
        );
    }

    /**
     * Draw background
     */
    protected function drawBackground(&$pdf, &$page, $item, $data) {
        //set fill color
        $position = $this->getPosition($item, $pdf, $data);
        $size = $this->getSize($item, $pdf, $data);
        $backgroundColor = $item->getAttribute('bgcolor');
        $color = $this->buildZendColor($backgroundColor);
        $page->setFillColor($color);

        //Draw background
        $page->drawRectangle($position['left'], $position['top'], $position['left'] + $size['width'], $position['top'] - $size['height'], Zend_Pdf_Page::SHAPE_DRAW_FILL
        );
    }

    /**
     * Apply modifier
     */
    public function applyModifier($node, $text, $data) {
        $modifierNodes = $node->getElementsByTagName('modifier');
        foreach ($modifierNodes as $modifierNode) {
            $modifierType = $modifierNode->getAttribute('type');
            $modifierParam = $modifierNode->getAttribute('param');
            $modifierParam = $this->replaceCodes($modifierParam, $data);


            $text = mage::getModel('GlobalPDF/Modifier')->apply($modifierType, $modifierParam, $text);
        }

        return $text;
    }

    /**
     * Replace {###} codes
     */
    protected function replaceCodes($text, $data) {
        $before = $text;

        //regex to find {XX} occurences
        $pattern = "/(\{([^\}]*)\})/";
        $matches = array();
        preg_match_all($pattern, $text, $matches);

        //add custom datas to $data
        $data = $this->custom_array_replace($data, mage::helper('GlobalPDF')->getCustomData());

        //add PDF datas
        $data['y'] = $this->_pdf->y;
        $data['x'] = $this->_pdf->x;

        //replace codes
        foreach ($matches[2] as $code) {

            if (isset($data[$code]))
                $text = str_replace('{' . $code . '}', $data[$code], $text);
        }

        //echo '<p>'.$before.' = '.$text.'</p>';
        //var_dump($matches);

        return $text;
    }

    /**
     * Translate [###] codes
     */
    protected function translate($text) {
        $before = $text;

        //regex to find [XX] occurences
        $pattern = "/(\[([^\}]*)\])/";
        $matches = array();
        preg_match_all($pattern, $text, $matches);

        //replace codes
        foreach ($matches[2] as $code) {
            $translation = mage::helper('GlobalPDF/Translate')->translate($code);
            $text = str_replace('[' . $code . ']', $translation, $text);
        }

        //echo '<p>'.$before.' = '.$text.'</p>';
        //var_dump($matches);

        return $text;
    }

    /**
     * Draw information for debug mode
     */
    private function drawDebug(&$pdf, &$page, $item, $data) {
        
        $position = $this->getPosition($item, $pdf, $data);
        $size = $this->getSize($item, $pdf, $data);
        $font = $this->getFont($item, $pdf, $data);
        
        if ((!is_numeric($position['left'])) || (!is_numeric($position['top'])))
            return false;

        //Draw border
        $this->drawBorder($pdf, $page, $item, $data);

        //add position
        $txt = 'x=' . $position['left'] . ',y=' . $position['top'] . ',w=' . $size['width'] . ',h=' . $size['height'];
        $page->setFont($this->getFontFromName($font['face'], $font['size']) ,$font['size']);
        $pdf->drawTextInBlock($page, $txt, $position['left'], $position['top'], 200, 10, 'l'
        );
    }

    protected function custom_array_replace(array $array, array $array1) {
        $args = func_get_args();
        $count = func_num_args();

        for ($i = 0; $i < $count; ++$i) {
            if (is_array($args[$i])) {
                foreach ($args[$i] as $key => $val) {
                    $array[$key] = $val;
                }
            }
        }

        return $array;
    }
    
    /**
     * Return font 
     * @param type $fontName
     * @param type $fontSize
     * @return type 
     */
    protected function getFontFromName($fontName, $fontSize)
    {
        return Mage::helper('GlobalPDF/Font')->getFont($fontName, $fontSize);
    }

}
