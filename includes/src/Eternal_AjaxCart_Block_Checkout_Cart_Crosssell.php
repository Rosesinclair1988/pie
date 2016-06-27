<?php
class Eternal_AjaxCart_Block_Checkout_Cart_Crosssell extends Mage_Checkout_Block_Cart_Crosssell
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
