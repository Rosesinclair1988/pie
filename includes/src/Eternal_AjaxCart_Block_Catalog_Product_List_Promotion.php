<?php
class Eternal_AjaxCart_Block_Catalog_Product_List_Promotion extends Mage_Catalog_Block_Product_List_Promotion
{
    /**
     * Get add to cart Url
     *
     * @return string
     */
    public function getAddToCartUrl($product, $additional = array())
    {
        $AddToCartUrl = parent::getAddToCartUrl($product, $additional);
        switch ($product['type_id']){
            case 'simple':{
                return $AddToCartUrl;
            }
            break;
            default:
                return $AddToCartUrl.'?options=cart';
                break;
        }
        return $AddToCartUrl;
    }

}
