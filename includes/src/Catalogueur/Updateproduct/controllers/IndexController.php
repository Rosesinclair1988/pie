<?php

class Catalogueur_Updateproduct_IndexController extends Mage_Core_Controller_Front_Action
{
	public function IndexAction()
	{
		if(@isset($_GET['storeCode']))
		$storeId=Mage::app()->getStore($_GET['storeCode'])->getId();
		elseif(@isset($_GET['storeId']))
		$storeId=$_GET['storeId'];
		else
		$storeId= 0;
		
		$this->GetHeader($this->getContent($_GET['format'],$storeId),$_GET['format']);
	}

	protected function GetHeader($content, $format = 'txt', $contentType = 'text/plain', $contentLength = null)
	{
		if( ($format =='csv') || ($format =='txt'))
		$contentType='text/plain';
		elseif($format =='xml')
		$contentType='text/xml';
		else
		exit();
		$this->getResponse()
		->setHttpResponseCode(200)
		->setHeader('Pragma', 'public', true)
		->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
		->setHeader('Content-type', $contentType, true)
		->setHeader('Content-Length', strlen($content))
		->setBody($content);
	}

	public function getContent($contentType = 'txt', $storeId = 0)
	{
		set_time_limit(0);
		ini_set('memory_limit', '256M');
		ini_set('display_errors', 1);
		error_reporting(E_ALL ^ E_NOTICE);

		require_once 'app/Mage.php';
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		try
		{
			include_once("application.key.php");
				
			if($cle1!="" &&  $cle2!="" && $_GET["c1"]!="" && $_GET["c2"]!=""){
				if($_GET["c1"] == $cle1 && $_GET["c2"] == $cle2){
					$ref = $_GET["r"];
					$titre = $_GET["t"];
					
					if($ref!="" && $titre!="") {						
						$product = Mage::getModel('catalog/product');
						$product->load($product->getIdBySku($ref));
						if($storeId!=0){
							$product->setStoreId($storeId);
						}
						$product->setName($titre);
						$product->save(); 
						unset($product);
						$return = "OK";
					}
				}else{
					$return = "KEYS";
				}
			}else{
					$return = "NOKEYS";
			}
			return $return;
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
	}
}
