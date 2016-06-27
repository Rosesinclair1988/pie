<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_Questions
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Questions index controller
 *
 * @category   Mage
 * @package    Mage_Questions
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Quotes_IndexController extends Mage_Core_Controller_Front_Action
{

    const XML_PATH_EMAIL_RECIPIENT  = 'quotes/email/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'quotes/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'quotes/email/email_template';
    const XML_PATH_ENABLED          = 'quotes/quotes/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('quoteForm')
            ->setFormAction( Mage::getUrl('*/*/post') );

        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);


                $error = false;
                if (isset($post["photo"]) && $post["photo"]["error"] != 4) {
                    if (!is_uploaded_file($post["photo"]["tmp_name"]) || $post["photo"]["error"] != 0) {
                       $error = true;
                    }
                    if($error == false){
                        $allowed_types = array("image/jpeg");
                        if(!in_array($post["photo"]["type"], $allowed_types)){
                             $error = true;
                        }
                    }

                    if($error == false){
                        $img = imagecreatefromjpeg($post["photo"]["tmp_name"]);
                        if(!imageSX($img) || !imageSY($img) || imageSX($img) > 2000 || imageSY($img) > 2000){
                            $error = true;
                        }
                    }

                    if($error == false){
                        $file_name = md5($post["photo"]["tmp_name"] + rand()*100000);
                        $imageurl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'imageupload/'.$file_name.'.jpg';
                        imagejpeg($new_img,$imageurl);
                    }

                }
                if ($error) {
                    throw new Exception();
                }

                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('quotes')->__('Your quote request was submitted. Thank you.'));
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('customer/session')->addError(Mage::helper('quotes')->__('Unable to submit quote request. Please, try again later'));
                $this->_redirect('*/*/');
                return;
            }
        } else {
            $this->_redirect('*/*/');
        }
    }

}
