<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class MDN_GlobalPDF_Model_Pdfhelper extends Mage_Sales_Model_Order_Pdf_Abstract {

    public $_PAGE_HEIGHT = 842;
    public $_PAGE_WIDTH = 595;
    public $_footerHeight;
    public $pdf;
    protected $firstPageIndex = 0;
    private static $_minFontSize = 8;

    /**
     * Draw image
     */
    public function drawImage(&$page, $imagePath, $x, $y, $width, $height) {
        //load image as zend image
        $image = Zend_Pdf_Image::imageWithPath($imagePath);

        //print image
        $page->drawImage($image, $x, $y - $height, $x + $width, $y);
    }

    /**
     * Draw multiline text
     */
    public function DrawMultilineText(&$page, $Text, $x, $y, $width, $LineHeight, $maxLines, $enableNewPage = false, $maxHeight = 0, $padding = null) {

        //format and add char return to fit the width
        $Text = $this->formatTextForPdf($Text);
        $usedWidth = $width;
        $usedWidth -= ($padding['left'] + $padding['right']);
        $Text = $this->WrapTextToWidth($page, $Text, $usedWidth);


        $tabText = explode("\n", $Text);

        // max_height is defined in xml file
        if ($maxHeight != 0) {

            // retieve nbr lines
            $tabLines = explode("\n", $Text);
            $tabText = array();

            $nbrLines = 0;
            foreach ($tabLines as $line) {
                if ($line != "") {
                    $tabText[] = $line;
                }
            }

            $nbrLines = count($tabText);

            // check height
            $height = $nbrLines * $LineHeight;

            if ($height > $maxHeight) {

                $LineHeight = $maxHeight / $nbrLines;
                $newFontSize = $LineHeight - 3;

                // check font size
                if ($newFontSize < self::$_minFontSize) {

                    $newFontSize = self::$_minFontSize;
                    $LineHeight = self::$_minFontSize + 3;

                    $maxLines = $maxHeight / $LineHeight;

                    // break tab, keep only $maxLines lines
                    $tmp = array_chunk($tabText, $maxLines);

                    $tabText = $tmp[0];
                }

                $page->setFont($page->getFont(), $newFontSize);
            }
        }

        $retour = 0;
        $i = 1;
        foreach ($tabText as $value) {
            if ($value !== '') {

                $page->drawText(trim(strip_tags($value)), $x + $padding['left'], $y, 'UTF-8');
                $y -=$LineHeight;
                $retour += $LineHeight;

                //manage max lines
                if ($maxLines && $maxLines <= $i)
                    break;

                //check for new page
                if (($y < $this->_footerHeight) && ($enableNewPage)) {

                    $savedFont = $page->getFont();
                    $savedFontSize = $page->getFontSize();

                    $page = $this->NewPage();
                    $y = $this->y;
                    $retour = 0;

                    //re apply font (because new page can change font settings
                    $page->setFont($savedFont, $savedFontSize);
                }

                $i++;
            }
        }
        return $retour;
    }

    /**
     * Return multiline text height
     *
     * @param unknown_type $page
     * @param unknown_type $Text
     * @param unknown_type $Size
     * @param unknown_type $LineHeight
     * @return unknown
     */
    public function getMultilineTextHeight(&$page, $Text, $Size, $LineHeight) {
        $retour = -$LineHeight;
        $page->setFont(Mage::helper('GlobalPDF/Font')->getFont(Zend_Pdf_Font::FONT_HELVETICA, $Size), $Size);
        foreach (explode("\n", $Text) as $value) {
            if ($value !== '') {
                $retour += $LineHeight;
            }
        }
        return $retour;
    }

    /**
     * Return text width with specified font
     */
    public function widthForStringUsingFontSize($string, $font, $fontSize) {
        try {
            $string = trim(strip_tags($string));
            $drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $string);
            $characters = array();
            for ($i = 0; $i < strlen($drawingString); $i++) {
                $characters[] = (ord($drawingString[$i++]) << 8 ) | ord($drawingString[$i]);
            }
            $glyphs = $font->glyphNumbersForCharacters($characters);
            $widths = $font->widthsForGlyphs($glyphs);
            $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;

            return $stringWidth;
        } catch (Exception $ex) {
            die("Unable to evaluate string width for string = " . $string);
        }
    }

    /**
     * Draw text in block with alignment settings
     *
     * @param unknown_type $page
     * @param unknown_type $text
     * @param unknown_type $x
     * @param unknown_type $y
     * @param unknown_type $width
     * @param unknown_type $height
     * @param unknown_type $alignment
     */
    public function drawTextInBlock(&$page, $text, $x, $y, $width, $height, $alignment = 'c', $encoding = 'UTF-8') {

        $text = $this->formatTextForPdf($text);
        $text_width = $this->widthForStringUsingFontSize($text, $page->getFont(), $page->getFontSize());



        //if text_width larger that width, truncate it
        if ($text_width > $width) {
            $text = $this->TruncateTextToWidth($page, $text, $width - 5); //add a margin
            $text_width = $this->widthForStringUsingFontSize($text, $page->getFont(), $page->getFontSize());
        }

        switch ($alignment) {
            case 'c': //on centre le texte dans le bloc
                $x = $x + ($width / 2) - $text_width / 2;
                break;
            case 'r': //on aligne � droite
                $x = $x + $width - $text_width;
        }

        $page->drawText(trim(strip_tags($text)), $x, $y, $encoding);
    }

    /**
     * Add new page to PDF
     *
     */
    public function NewPage(array $settings = array(), $data = array()) {

        if (array_key_exists('store_id', $settings)) {
            $data['store_id'] = $settings['store_id'];
        }


        $singleton = mage::getSingleton('GlobalPDF/Event');

        //before new page event
        if (count($this->pdf->pages) > 0) {
            $lastPage = $this->pdf->pages[count($this->pdf->pages) - 1];
            $singleton->playEvent('before_new_page', $this, $lastPage, array());
        }

        $page = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->pdf->pages[] = $page;
        $this->y = 830;

        //update page num
        mage::helper('GlobalPDF')->addCustomData('page_number', count($this->pdf->pages));

        //draw header template
        $headerTemplatePath = mage::helper('GlobalPDF')->getHeaderTemplatePath();
        $headerXml = file_get_contents($headerTemplatePath);
        $this->drawTemplate($headerXml, $page, $data);

        //after new page event
        $singleton->playEvent('after_new_page', $this, $page, array());

        //draw footer template
        $footerTemplatePath = mage::helper('GlobalPDF')->getFooterTemplatePath();
        $footerXml = file_get_contents($footerTemplatePath);
        $this->drawTemplate($footerXml, $page, $data);

        return $page;
    }

    public function _afterGetPdf() {
        //after document event
        if (count($this->pdf->pages) > 0) {
            $lastPage = $this->pdf->pages[count($this->pdf->pages) - 1];
            mage::getSingleton('GlobalPDF/Event')->playEvent('after_document', $this, $lastPage, array());
        }

        parent::_afterGetPdf();
    }

    /**
     * Truncate text to get the expected max width
     *
     * @param unknown_type $text
     * @param unknown_type $width
     */
    public function TruncateTextToWidth($page, $text, $width) {
        $current_width = $this->widthForStringUsingFontSize($text, $page->getFont(), $page->getFontSize());
        while ($current_width > $width) {
            $text = substr($text, 0, strlen($text) - 1);
            $current_width = $this->widthForStringUsingFontSize($text, $page->getFont(), $page->getFontSize());
        }
        return $text;
    }

    /**
     * Add return char to text to ensure that lines width doesn't exceed width
     *
     * @param unknown_type $text
     * @param unknown_type $width
     */
    public function WrapTextToWidth($page, $text, $width) {

        $t_words = explode(' ', $text);

        //check that every words size is not bigger that width
        $tWordsFinal = array();
        for ($i = 0; $i < count($t_words); $i++) {
            $word = $t_words[$i];
            $wordSize = $this->widthForStringUsingFontSize($word, $page->getFont(), $page->getFontSize());
            if ($wordSize > $width) {
                $charCount = strlen($word);
                $previousWord = '';
                for ($j = 0; $j < $charCount; $j++) {
                    $currentWord = $previousWord . $word[$j];
                    $currentWordSize = $this->widthForStringUsingFontSize($currentWord, $page->getFont(), $page->getFontSize());
                    if ($currentWordSize > $width) {
                        $tWordsFinal[] = $previousWord;
                        $previousWord = $word[$j];
                    }
                    else
                        $previousWord = $currentWord;
                }
                $tWordsFinal[] = $previousWord;
            }
            else
                $tWordsFinal[] = $word;
        }

        //break lines according to split
        $retour = "";
        $current_line = "";
        $t_words = $tWordsFinal;
        for ($i = 0; $i < count($t_words); $i++) {
            if ($this->widthForStringUsingFontSize($current_line . ' ' . $t_words[$i], $page->getFont(), $page->getFontSize()) < $width)
                $current_line .= ' ' . $t_words[$i];
            else {
                if (($current_line != '') && (strlen($current_line) > 2))
                    $retour .= $current_line . "\n";
                $current_line = $t_words[$i];
            }

            if (!(strpos($t_words[$i], "\n") === false)) {
                if (($current_line != '') && (strlen($current_line) > 2))
                    $retour .= $current_line;
                $current_line = '';
            }
        }
        $retour .= $current_line;

        return $retour;
    }

    /**
     * Add pagination
     *
     */
    public function AddPagination($pdf) {
        $page_count = count($pdf->pages);
        for ($i = 0; $i < $page_count; $i++) {
            if ($i >= $this->firstPageIndex) {
                $page = $pdf->pages[$i];
                $pagination = ($i + 1 - $this->firstPageIndex) . ' / ' . ($page_count - $this->firstPageIndex);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
                $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
                $this->drawTextInBlock($page, $pagination, 0, 25, $this->_PAGE_WIDTH - 20, 40, 'r');
            }
        }
    }

    /**
     * Format address
     */
    public function FormatAddress($adress, $caption = '', $show_details = false, $NoTvaIntraco = '') {
        if ($NoTvaIntraco == 'taxvat')
            $NoTvaIntraco = '';
        $FormatedAddress = "";
        if ($caption != '')
            $FormatedAddress = $caption . "\n ";
        if ($adress != null) {
            if ($adress->getcompany() != '')
                $FormatedAddress .= $adress->getcompany() . "\n ";
            if ($adress->getPrefix() == '')
                $FormatedAddress .= 'M. ';
            $FormatedAddress .= $adress->getName() . "\n ";
            $FormatedAddress .= $adress->getStreet(1) . "\n ";
            if ($adress->getStreet(2) != '')
                $FormatedAddress .= $adress->getStreet(2) . "\n ";
            if ($show_details) {
                if ($adress->getbuilding() != '')
                    $FormatedAddress .= ' Bat ' . $adress->getbuilding();
                if ($adress->getfloor() != '')
                    $FormatedAddress .= ' Etage ' . $adress->getfloor();
                if ($adress->getdoor_code() != '')
                    $FormatedAddress .= ' Code ' . $adress->getdoor_code();
                if ($adress->getappartment() != '')
                    $FormatedAddress .= ' Appt ' . $adress->getappartment();
                $FormatedAddress .= "\n ";
            }
            $FormatedAddress .= $adress->getPostcode() . ' ' . $adress->getCity() . "\n ";
            $FormatedAddress .= strtoupper(Mage::getModel('directory/country')->load($adress->getCountry())->getName()) . "\n ";
            if ($show_details)
                $FormatedAddress .= $adress->getcomments() . "\n ";
            if ($NoTvaIntraco != '')
                $FormatedAddress .= 'No TVA : ' . $NoTvaIntraco;
        }
        return $FormatedAddress;
    }

    /**
     * Draw template
     */
    public function drawTemplate($xml, &$page, $data = null) {
        //todo : move in another place ?
        $data = mage::helper('GlobalPDF')->setAdditionalDatas($data);
        $model = mage::getModel('GlobalPDF/Template');
        $model->drawTemplate($this, $page, $xml, $data);
    }

    /**
     * Format text for pdf (from html)
     */
    public function formatTextForPdf($text) {
        $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
        $text = strip_tags($text);
        return $text;
    }

    /**
     * Add a new page if we reach footer
     */
    public function checkForNewPage(&$page) {
        if (($this->y < $this->_footerHeight)) {
            $savedFont = $page->getFont();
            $savedFontSize = $page->getFontSize();

            $page = $this->NewPage();
            $y = $this->y;
            $retour = 0;

            //re apply font (because new page can change font settings
            $page->setFont($savedFont, $savedFontSize);
        }
    }

    public function jumpToNewPage(&$page) {

        $savedFont = $page->getFont();
        $savedFontSize = $page->getFontSize();

        $page = $this->newPage();

        $page->setFont($savedFont, $savedFontSize);
    }

}
