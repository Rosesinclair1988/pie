<?php

set_time_limit(0);
ini_set('memory_limit', '256M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

class Catalogueur_Export_IndexController extends Mage_Core_Controller_Front_Action
{
	public function IndexAction()
	{
		if(@isset($_GET['storeCode']))
		$storeId=Mage::app()->getStore($_GET['storeCode'])->getId();
		elseif(@isset($_GET['storeId']))
		$storeId=$_GET['storeId'];
		else
		$storeId=0;
		
		$limit=(@isset($_GET['limit']))?$_GET['limit']:0;
		$offset=(@isset($_GET['offset']))?$_GET['offset']:0;
		
		$this->GetHeader($this->getContent($_GET['format'],$storeId,$_GET['limit'],$_GET['offset']),$_GET['format']);
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

	public function getContent($contentType = 'txt', $storeId = 0, $limit=null, $offset=null)
	{
		require_once 'app/Mage.php';
		Mage::app('default');
		try
		{
			$products = Mage::getModel('catalog/product')->getCollection();
			// $products
			if($storeId != 0)
			{
				$products->addStoreFilter($storeId);
				$products->joinAttribute('description', 'catalog_product/description', 'entity_id', null, 'inner', $storeId);
			}
			$products->addAttributeToFilter('status', 1);//enabled
			$products->addAttributeToFilter('visibility', 4);//catalog, search
			$products->addAttributeToSelect('*');
			$prodIds=$products->getAllIds($limit,$offset);

			//$product = Mage::getModel('catalog/product');
			$heading = array('ID','WEIGHT','MANUFACTURER','NAME','DESCRIPTION','PRICE','PRICE_PROMO','PROMO_FROM','PROMO_TO','STOCK','QUANTITY','URL','IMAGE','FDP','CATEGORY');

			//setEntityTypeFilter(4) => Product Entity
			$attributesInfo = Mage::getResourceModel('eav/entity_attribute_collection')
			->setEntityTypeFilter(4)
			->addSetInfo()
			->getData();
			foreach($attributesInfo as $attribute)
			{
				$code = $attribute['attribute_code'];
				$is_user_defined = $attribute['is_user_defined'];
				if($is_user_defined==1)
				{
					if(!in_array('ATT_'.strtoupper($code), $heading))
					{
						array_push($heading, 'ATT_'.strtoupper($code));
					}
				}
			}
			if( ($contentType =='csv') || ($contentType =='txt'))
			{
				$feed_line=implode("|", $heading)."\r\n";
			}
			elseif($contentType =='xml')
			{
				$feed_line='<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<catalogue lang="FR">'.PHP_EOL;
			}
			else
			{
				exit();
			}
			$buffer = $feed_line;
 
			$all_categories = array();
			$categories = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect(array('level', 'name')) -> load();
			foreach($categories as $category){
				$all_categories[$category->getId()] = $category;
			}

			foreach($prodIds as $productId)
			{
				$product = Mage::getModel('catalog/product');
				$product->load($productId);

				$product_data = array();
				
				// Catégories
				$product_categories = array();
 				foreach($product->getCategoryIds() as $_categoryId){
					$product_categories[$all_categories[$_categoryId]->getLevel()-1] = $_categoryId;
 				}
 				
				ksort($product_categories);
				
				$main_category = $all_categories[end($product_categories)];
				
 				$categories = array_fill(1, 6, "");
				
				if($main_category){
					$parent_categories = $main_category->getParentCategories();
					$cpt=1;
					foreach($parent_categories as $category){
						$level=$category->getLevel()-1;
						$categories[($level>0)?$level:$cpt] = $category->getName();
						// $product_data['category'.($category->getLevel()-1)] = $category->getName();
						$cpt++;
					}
					// $categories[$main_category->getLevel()-1] = $main_category->getName();
					// $product_data['category'.($main_category->getLevel()-1)] = $main_category->getName();

					ksort($categories);
				}				

				foreach($categories as $level => $category_name){
					$product_data['categorie'.$level] = $category_name;
				}
				
				$marque=$product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product);
				if($marque=="No" || $marque=="Non") $marque="";
				
				$product_data['marque']=$marque;
				$product_data['identifiant_unique']=$product->getSku();
				$product_data['titre']=$product->getName();
				$product_data['meta_title']=$product->getMetaTitle();
				$product_data['meta_keyword']=$product->getMetaKeyword();
				$product_data['meta_description']=$product->getMetaDescription();
				$product_data['description']=$product->getDescription();
				$product_data['prixBarre']=$product->getSpecialPrice();
				$product_data['prix']=$product->getPrice();
				$product_data['date_debut_promo']=$product->getSpecialFromDate();
				$product_data['date_fin_promo']=$product->getSpecialToDate();
				$product_data['poids']=$product->getWeight();
				if(Mage::getStoreConfig('catalog/seo/product_use_categories')){
					// get the products direct category. this will work with products that are in sub categories
					// i.e.   category1/category2/.../categoryN/a-product
					$categoryId = $product->getCategoryIds();
					if(is_array($categoryId)){
					$categoryId = array_pop($categoryId);
					}
					$category = Mage::getModel('catalog/category')->load($categoryId);
					// add base url to a call to getUrlPath. Make sure to trim the forward slash on the return from the urlPath call
					$product_data['url_produit']=Mage::getBaseUrl().trim(Mage::getModel('catalog/product_url')->getUrlPath($product,$category),"/");
				}else{
					$product_data['url_produit']=$product->getProductUrl();
				}
				$product_data['url_image']=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage();
				
				
				if($product->getTypeId()=="configurable"){
					$references="";
					$childProducts = Mage::getModel('catalog/product_type_configurable')
							->getUsedProducts(null,$product);
					foreach($childProducts as $childProduct)
					{
						//$product_data[$attribute['attribute_code']]=$attribute['attribute_code'];
						$references .= '<ref><![CDATA['.$childProduct->getSku().']]></ref>'."\r\n";
					}
					$product_data['references']=$references;
				}else{
					$product_data['references']='<ref><![CDATA['.$product->getSku().']]></ref>'."\r\n";
				}

				foreach($attributesInfo as $attribute)
				{
					//$product_data[$attribute['attribute_code']]=$attribute['attribute_code'];
					$code = $attribute['attribute_code'];
					$is_user_defined = $attribute['is_user_defined'];
					if($is_user_defined==1)
					{
						$value = $product->getResource()->getAttribute($code)->getFrontend()->getValue($product);
					//	echo "code:".($code)." - value:".$value."\n";
						$product_data[$code]=$value;
					}
				}

				$bad=array('/[\x00-\x1f]/i','/[\x7F]/i','/"/',"/\r\n/","/\n/","/\r/","/\t/");
				$good=array(" "," ",""," "," "," ","");
				foreach($product_data as $k=>$val)
				{
					// $product_data[$k] = '"'.str_replace($bad,$good,$val).'"';
					$product_data[$k] = html_entity_decode(htmlspecialchars(strip_tags(preg_replace($bad,$good,$val))));
				}

				if( ($contentType =='csv') || ($contentType =='txt'))
				{
					$feed_line = implode("|", $product_data)."\r\n";
				}
				elseif($contentType =='xml')
				{
					$feed_line = '<product> ';
					foreach($product_data as $k=>$val)
					{
						if($k=='references') $feed_line .= '<'.$k.'>'.$val.'</'.$k.'>'.PHP_EOL;
						else $feed_line .= '<'.$k.'><![CDATA['.$val.']]></'.$k.'>'.PHP_EOL;
					}
					$feed_line .= '</product>'.PHP_EOL;
				}
				else
				exit();
				$buffer .= $feed_line;
				unset($product_data);
				unset($product);
			}
			if($contentType == 'xml')
			$buffer .='</catalogue>';
			return $buffer;
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
	}
}
