<?php

class Eternal_Venedor_Block_Adminhtml_System_Config_Form_Field_Color extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Add color picker
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $element->getElementHtml(); //Default HTML
        $jqPath = $this->getJsUrl('eternal/jquery/jquery-1.8.2.min.js');
        $mcPath = $this->getJsUrl('eternal/jquery/mcolorpicker/');
        
        if (Mage::registry('jqueryLoaded') == false)
        {
            $html .= '
            <script type="text/javascript" src="'. $jqPath .'"></script>
            <script type="text/javascript">jQuery.noConflict();</script>
            ';
            Mage::register('jqueryLoaded', 1);
        }
        if (Mage::registry('colorPickerLoaded') == false)
        {
            $html .= '
            <script type="text/javascript" src="'. $mcPath .'mcolorpicker.min.js"></script>
            <script type="text/javascript">
                jQuery.fn.mColorPicker.init.replace = false;
                jQuery.fn.mColorPicker.defaults.imageFolder = "'. $mcPath .'images/";
                jQuery.fn.mColorPicker.init.allowTransparency = true;
                jQuery.fn.mColorPicker.init.showLogo = false;
            </script>
            ';
            Mage::register('colorPickerLoaded', 1);
        }
        
        $html .= '
            <script type="text/javascript">
                jQuery(function($){
                    $("#'. $element->getHtmlId() .'").attr("data-hex", true).width("250px").mColorPicker();
        ';
        
        if (strpos($element->getHtmlId(), '_bg_color') !== false)
            $html .= '
                    $("#'. $element->getHtmlId() .'").change(function() {
                        $("#'. str_replace('_bg_color', '_texture_preview', $element->getHtmlId()) .'").css(
                            "background-color", $("#'. $element->getHtmlId() .'").val()
                        )       
                    });
                    ';
            
        $html .='
            });
            </script>
        ';
        
        return $html;
    }
}
