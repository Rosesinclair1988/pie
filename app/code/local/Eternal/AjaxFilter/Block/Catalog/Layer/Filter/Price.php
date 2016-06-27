<?php

class Eternal_AjaxFilter_Block_Catalog_Layer_Filter_Price extends Mage_Catalog_Block_Layer_Filter_Price 
{
        
    public $_currentCategory;
    public $_searchSession;
    public $_productCollection;
    public $_maxPrice;
    public $_minPrice;
    public $_currMinPrice;
    public $_currMaxPrice;
    public $_helper;
    
    /*
    * 
    * Set all the required data that our slider will require
    * Set current _currentCategory, _searchSession, setProductCollection, setMinPrice, setMaxPrice, setCurrentPrices
    * 
    * @set all required data
    * 
    */
    public function __construct(){
    
        $this->_currentCategory = Mage::registry('current_category');
        $this->_searchSession = Mage::getSingleton('catalogsearch/session');
        $this->setProductCollection();
        $this->setMinPrice();
        $this->setMaxPrice();
        $this->setCurrentPrices();
        $this->_helper = Mage::helper('eternal_ajaxfilter');
        
        parent::__construct();        
    }
    
    /*
    * 
    * Check whether the slider is enabled.
    *
    * @return boolean
    * 
    */
    public function getSliderStatus(){
        if($this->_helper->getConfig('price_slider/enable'))
            return true;
        else
            return false;            
    }
         
    public function isTextBoxEnabled(){
        return $this->_helper->getConfig('price_slider/textbox');    
    }
    
    public function getFromLabel(){
        return $this->_helper->getConfig('price_slider/fromText');    
    }
    
    public function getStep(){
        return $this->_helper->getConfig('price_slider/step');    
    }
    
    public function getToLabel(){
        return $this->_helper->getConfig('price_slider/toText');    
    }
    
    public function getGoBtnText(){
        return $this->_helper->getConfig('price_slider/goBtnText');    
    }
    
    public function getClearBtnText(){
        return $this->_helper->getConfig('price_slider/clearBtnText');    
    }
    
    public function getPriceDisplayType(){
        if($this->isTextBoxEnabled()){
            $html = '
                <div class="slider-wrap show-textbox"><input id="price-slider" type="hidden" name="price" value="'.$this->getCurrMinPrice().';'.$this->getCurrMaxPrice().'" /></div>
                <div class="text-box">
                    <label for="minPrice">'.$this->getFromLabel().'</label><input type="text" name="min" id="minPrice" class="priceTextBox" value="'.$this->getCurrMinPrice().'"/>
                    <label for="maxPrice">'.$this->getToLabel().'</label><input type="text" name="max" id="maxPrice" class="priceTextBox" value="'.$this->getCurrMaxPrice().'"/>
                </div><div class="actions">
                    <input type="button" value="'.$this->getGoBtnText().'" name="go" class="button go"/>
                    <input type="button" value="'.$this->getClearBtnText().'" name="clear" class="button clear"/>
                    <input type="hidden" id="amount" readonly="readonly" style="background:none; border:none;" value="'.$this->getCurrencySymbol().$this->getCurrMinPrice()." - ".$this->getCurrencySymbol().$this->getCurrMaxPrice().'" />
                </div>';
        }else{
            $html = '
                <div class="slider-wrap"><input id="price-slider" type="hidden" name="price" value="'.$this->getCurrMinPrice().';'.$this->getCurrMaxPrice().'" /></div>
                    <input type="hidden" id="amount" readonly="readonly" style="background:none; border:none;" value="'.$this->getCurrencySymbol().$this->getCurrMinPrice()." - ".$this->getCurrencySymbol().$this->getCurrMaxPrice().'" />
                    ';    
        }
        return $html;
    }
    
    /**
    *
    * Prepare html for slider and add JS that incorporates the slider.
    *
    * @return html
    *
    */
    
    public function getHtml(){
        
        if($this->getSliderStatus()){
            $text='
                <div class="price-slider">
                    '.$this->getPriceDisplayType().'
                    <div id="slider-range"></div>
                    
                </div>'.$this->getSliderJs();    
            
            return $text;
        } 
        return parent::getHtml();   
    }
    
    /*
    * Prepare query string that was in the original url 
    *
    * @return queryString
    */
    public function prepareParams(){
        $url="";
    
        $params=$this->getRequest()->getParams();
        foreach ($params as $key=>$val) {
            $val = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $val);
            $val = preg_replace('/\s+/', '', $val);
            $val = str_replace(array('<script>', '</script>', '"', '\'', ';'), array('', '', '', '', ''), $val);
            if($key=='id'){ continue;}
            if($key=='min'){ continue;}
            if($key=='max'){ continue;}
            $url.='&'.$key.'='.$val;
        }
        return $url;
    }
    
    /*
    * Fetch Current Currency symbol
    * 
    * @return currency
    */
    public function getCurrencySymbol(){
        return Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
    }
    
    /*
    * Fetch Current Minimum Price
    * 
    * @return price
    */
    public function getCurrMinPrice(){
        if($this->_currMinPrice > 0){
            $min = $this->_currMinPrice;
        } else{
            $min = $this->_minPrice;
        }
        return $min;
    }
    
    /*
    * Fetch Current Maximum Price
    * 
    * @return price
    */
    public function getCurrMaxPrice(){
        if($this->_currMaxPrice > 0){
            $max = $this->_currMaxPrice;
        } else{
            $max = $this->_maxPrice;
        }
        return $max;
    }
    
    /*
    * Get Slider Configuration TimeOut
    * 
    * @return timeout
    */
    public function getConfigTimeOut(){
        return $this->_helper->getConfig('price_slider/timeout');
    }
    
    
    /*
    * Gives you the current url without parameters
    * 
    * @return url
    */
    public function getCurrentUrlWithoutParams(){
        $baseUrl = explode('?',Mage::getUrl('',array('_current'=>true,'_use_rewrite'=>true)));
        $baseUrl = $baseUrl[0];
        return $baseUrl;
    }
    
    /*
    * Check slider Ajax enabled
    * 
    * @return boolean
    */
    public function isAjaxSliderEnabled(){
        return $this->_helper->getConfig('general/slider');
    }
    
    /*
    * Get JS that brings the slider in Action
    * 
    * @return JavaScript
    */
    public function getSliderJs(){
        
        $baseUrl = $this->getCurrentUrlWithoutParams();
        $timeout = $this->getConfigTimeOut();
        
        if($this->isAjaxSliderEnabled()){
            $ajaxCall = 'sliderAjax(url);';
        }else{
            $ajaxCall = 'window.location=url;';
        }
        
        if($this->isTextBoxEnabled()){
            $updateTextBoxPriceJs = '
                            // Update TextBox Price
                            $("#minPrice").val(newMinPrice); 
                            $("#maxPrice").val(newMaxPrice);';
        }
        
        
        $html = '
            <script type="text/javascript">
                jQuery(function($) {
                    var newMinPrice, newMaxPrice, url, temp, timer;
                    var categoryMinPrice = '.$this->_minPrice.';
                    var categoryMaxPrice = '.$this->_maxPrice.';
                    var sliderStep = '.$this->getStep().';
                    var showTextBox = '.($this->isTextBoxEnabled()?'true':'false').';
                    function isNumber(n) {
                      return !isNaN(parseFloat(n)) && isFinite(n);
                    }
                    
                    var price_slider = $( "#price-slider").slider({
                        from: categoryMinPrice, 
                        to: categoryMaxPrice, 
                        step: sliderStep, 
                        smooth: true, 
                        round: 2,
                        format: {
                            format: "'.$this->getCurrencySymbol().'#,###.##"
                        }, 
                        dimension: "",
                        onstatechange: function(value) {
                            values = value.split(";");
                            newMinPrice = values[0];
                            newMaxPrice = values[1];
                            $( "#amount" ).val( "'.$this->getCurrencySymbol().'" + newMinPrice + " - '.$this->getCurrencySymbol().'" + newMaxPrice );
                            '.$updateTextBoxPriceJs.'
                        },
                        callback: function(value) {
                            // Current Min and Max Price
                            values = value.split(";");
                            var newMinPrice = values[0];
                            var newMaxPrice = values[1];
                            
                            // Update Text Price
                            $( "#amount" ).val( "'.$this->getCurrencySymbol().'"+newMinPrice+" - '.$this->getCurrencySymbol().'"+newMaxPrice );
                            
                            '.$updateTextBoxPriceJs.'
                            
                            url = getUrl(newMinPrice,newMaxPrice);
                            if (!showTextBox) {
                                if (timer) clearTimeout(timer);
                                //window.location= url;
                                timer = setTimeout(function(){
                                    '.$ajaxCall.'
                                }, '.$timeout.');     
                            }
                        }
                    });
                    
                    $(".priceTextBox").focus(function(){
                        temp = $(this).val();    
                    });
                    
                    $(".priceTextBox").keyup(function(){
                        var value = $(this).val();
                        if(!isNumber(value)){
                            $(this).val(temp);    
                        }
                    });
                    
                    $(".priceTextBox").keypress(function(e){
                        if(e.keyCode == 13){
                            var value = $(this).val();
                            if(value < categoryMinPrice || value > categoryMaxPrice){
                                $(this).val(temp);    
                            }
                            url = getUrl($("#minPrice").val(), $("#maxPrice").val());
                            '.$ajaxCall.'    
                        }    
                    });
                    
                    $(".priceTextBox").blur(function(){
                        var value = $(this).val();
                        if(value < categoryMinPrice || value > categoryMaxPrice){
                            $(this).val(temp);    
                        }                        
                    });
                    
                    $(".go").click(function(){
                        url = getUrl($("#minPrice").val(), $("#maxPrice").val());
                        '.$ajaxCall.'    
                    });
                    
                    $("#minPrice").change(function() {
                        $("#price-slider").slider("value", $(this).val(), $("#maxPrice").val());
                    });
                    
                    $("#maxPrice").change(function() {
                        $("#price-slider").slider("value", $("#minPrice").val(), $(this).val());
                    });
                    
                    $(".clear").click(function(){
                        $("#minPrice").val(categoryMinPrice);
                        $("#maxPrice").val(categoryMaxPrice);
                        $("#price-slider").slider("value", categoryMinPrice, categoryMaxPrice);
                        url = getUrl($("#minPrice").val(), $("#maxPrice").val());
                        '.$ajaxCall.'
                    });
                    
                    function getUrl(newMinPrice, newMaxPrice){
                        return "'.$baseUrl.'"+"?min="+newMinPrice+"&max="+newMaxPrice+"'.$this->prepareParams().'";
                    }
                });
            </script>
        ';    
        
        return $html;
    }    
    
    /*
    * Get the Slider config 
    *
    * @return object
    */
    public function getConfig($key){
        return Mage::getStoreConfig($key);
    }
    
    
    /*
    * Set the Actual Min Price of the search and catalog collection
    *
    * @use category | search collection
    */
    public function setMinPrice(){
        if( (isset($_GET['q']) && !isset($_GET['min'])) || !isset($_GET['q'])){
            $this->_minPrice = $this->_productCollection->getMinPrice();
            $this->_searchSession->setMinPrice($this->_minPrice);        
        }else{
            $this->_minPrice = $this->_searchSession->getMinPrice();
        }
    }
    
    /*
    * Set the Actual Max Price of the search and catalog collection
    *
    * @use category | search collection
    */
    public function setMaxPrice(){
        if( (isset($_GET['q']) && !isset($_GET['max'])) || !isset($_GET['q'])){
            $this->_maxPrice = $this->_productCollection->getMaxPrice();
            $this->_searchSession->setMaxPrice($this->_maxPrice);
        }else{
            $this->_maxPrice = $this->_searchSession->getMaxPrice();
        }
    }
    
    /*
    * Set the Product collection based on the page server to user 
    * Might be a category or search page
    *
    * @set /*
    * Set the Product collection based on the page server to user 
    * Might be a category or search page
    *
    * @set Mage_Catalogsearch_Model_Layer 
    * @set Mage_Catalog_Model_Layer    
    */
    public function setProductCollection(){
        
        if($this->_currentCategory){
            $this->_productCollection = $this->_currentCategory
                            ->getProductCollection()
                            ->addAttributeToSelect('*')
                            ->setOrder('price', 'ASC');
        }else{
            $this->_productCollection = Mage::getSingleton('catalogsearch/layer')->getProductCollection();
        }                    
    }
    
    
    /*
    * Set Current Max and Min Prices choosed by the user
    *
    * @set price
    */
    public function setCurrentPrices(){
        
        $this->_currMinPrice = $this->getRequest()->getParam('min');
        $this->_currMaxPrice = $this->getRequest()->getParam('max'); 
    }    
    
    /*
    * Set Current Max and Min Prices choosed by the user
    *
    * @set price
    */
    public function baseToCurrent($srcPrice){
        $store = $this->getStore();
        return $store->convertPrice($srcPrice, false, false);
    }
    
    
    /*
    * Retrive store object
    *
    * @return object
    */
    public function getStore(){
        return Mage::app()->getStore();
    }
}
