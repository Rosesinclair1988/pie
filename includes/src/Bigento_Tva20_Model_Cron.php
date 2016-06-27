<?php

class Bigento_Tva20_Model_Cron
{

    public static function apply()
    {
        mage::helper('bgtva20')->applyTVA20();
    }

}
