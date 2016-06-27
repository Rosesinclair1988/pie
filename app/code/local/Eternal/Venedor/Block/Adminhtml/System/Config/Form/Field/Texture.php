<?php

class Eternal_Venedor_Block_Adminhtml_System_Config_Form_Field_Texture extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Add texture preview
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $element->getElementHtml(); //Default HTML
        $jqUrl = $this->getJsUrl('eternal/jquery/jquery-1.8.2.min.js');
        $texUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . Mage::helper('venedor')->getTexturePath();
        
        //Recreate ID of the background color picker which is related with this pattern
        $bgcPickerId = str_replace('_texture', '_bg_color', $element->getHtmlId());
        
        //Create ID of the pattern preview box
        $previewId = $element->getHtmlId() . '_preview';
        
        if (Mage::registry('jqueryLoaded') == false)
        {
            $html .= '
            <script type="text/javascript" src="'. $jqUrl .'"></script>
            <script type="text/javascript">jQuery.noConflict();</script>
            ';
            Mage::register('jqueryLoaded', 1);
        }

        $html .= '
        <br/><div id="'. $previewId .'" style="width:280px; height:150px; margin:10px 0; background-color:transparent;"></div>
        <script type="text/javascript">
            jQuery(function($){
                var tex        = $("#'. $element->getHtmlId()    .'");
                var bgc        = $("#'. $bgcPickerId            .'");
                var preview    = $("#'. $previewId                .'");
                
                preview.css("background-color", bgc.attr("value"));
                
                tex.change(function() {
                    preview.css({
                        "background-color": bgc.css("background-color"),
                        "background-image": "url('. $texUrl .'" + tex.val() + ".png)"
                    });
                })
                .change();
            });
        </script>
        ';
        
        return $html;
    }
}
