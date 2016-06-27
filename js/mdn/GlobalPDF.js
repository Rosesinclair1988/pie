//**************************************************************************************
//Select product for catalog
function selectProduct(productId, productName)
{
	//build html
	var html = '';
	html = productName + ', ';
	
	//append to div
	document.getElementById('div_products').innerHTML = document.getElementById('div_products').innerHTML + html;
	document.getElementById('product_ids').value += ',' + productId;
}
