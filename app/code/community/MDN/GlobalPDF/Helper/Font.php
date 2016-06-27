<?php

class MDN_GlobalPDF_Helper_Font extends Mage_Core_Helper_Abstract {

    /**
     * 
     * @param type $fontName
     * @param type $fontSize
     * @return type 
     */
    public function getFont($fontName, $fontSize) {
        if (Mage::getStoreConfig('globalpdf/general/use_magento_font') == 1) {
            return Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/LinLibertineFont/LinLibertineC_Re-2.8.0.ttf', $fontSize);
        } else {
            return Zend_Pdf_Font::fontWithName($fontName, $fontSize);
        }
    }

}
