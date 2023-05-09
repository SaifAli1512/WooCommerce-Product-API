<?php error_reporting(E_ALL & ~E_NOTICE);
/////Functions////
function removeSpaces($str)
{
	return addslashes(trim($str));
}

/////////{ Start User Related Functions ////////////
function getEmailByUserId($userId)
{
	if($userId!="")
	{
		$r = dbRow("SELECT email FROM user_accounts WHERE id='".removeSpaces($userId)."'");
		if($r!=false)
		{
			return $r['email'];
		}	
	}	
}

function getProducts(){
	$q = dbAll("SELECT import_limit, import_offset FROM importapiconfig where import_type = 'create'");
	$limit = $q[0]['import_limit'];
	$offset = $q[0]['import_offset'];

	$r = dbAll("
			SELECT 
				product.product_id, 
				product_name, 
				GROUP_CONCAT(DISTINCT category.category_name SEPARATOR ', ') AS category,
				colour_name,
				size_name,
				productcolour_quantity as product_qty,
				product_length,
				product_weight,
				product_length,
				product_width,
				product_code,
				plating as plating_name,
				tags_title,
				product_alias,
				product_metatitle,
				product_metakeywords,
				product_metadescription,
				productdescription_text,
				short_desc,
				product_sku,
		 	  	GROUP_CONCAT(DISTINCT productimage.productimage_big SEPARATOR '|') AS big_images,
		 	  	GROUP_CONCAT(DISTINCT productimage.productimage_small SEPARATOR '|') AS small_images
			FROM product
			LEFT JOIN productcategory
				ON productcategory.product_id = product.product_id
			LEFT JOIN category
				ON category.category_id = productcategory.category_id				
			LEFT JOIN productcolour 
				ON productcolour.product_id = product.product_id
			LEFT JOIN colour
				ON 	productcolour.colour_id = colour.colour_id
			LEFT JOIN plating
				ON  plating.plating_id = product.plating_id
			LEFT JOIN size
				ON 	productcolour.size_id = size.size_id
 			LEFT JOIN productimage
				ON productimage.product_id = productcolour.product_id AND productcolour.colour_id = productimage.colour_id
 			LEFT JOIN producttags
 				ON producttags.product_id = product.product_id
			LEFT JOIN tags
 				ON tags.tags_id = producttags.tags_id
 			LEFT JOIN okproductdescription
 				ON okproductdescription.product_id = product.product_id
 			LEFT JOIN okproshort_desc
 				ON okproshort_desc.product_id = product.product_id
 			WHERE 
 			    productcolour_quantity > 0 AND 
     			product.product_published = 1 AND 
     			product.product_code > 0
			GROUP BY productcolour.productcolour_id, productcategory.product_id
			ORDER BY product.product_id DESC
 			LIMIT $limit OFFSET $offset
		");
	
	if(count($r))
	    $q = dbAll("UPDATE importapiconfig SET import_offset = import_limit+import_offset, import_date = NOW() WHERE import_type = 'create'");
	return $r;
}

function getModifiedproducts(){
	$q = dbAll("SELECT import_limit, import_offset FROM importapiconfig where import_type = 'update'");
	$limit = $q[0]['import_limit'];
	$offset = $q[0]['import_offset'];

	$r = dbAll("
			SELECT 
				product.product_id, 
				product_name, 
				GROUP_CONCAT(DISTINCT category.category_name SEPARATOR ', ') AS category,
				colour_name,
				size_name,
				productcolour_quantity as product_qty,
				product_length,
				product_weight,
				product_length,
				product_width,
				product_code,
				plating as plating_name,
				tags_title,
				product_alias,
				product_metatitle,
				product_metakeywords,
				product_metadescription,
				productdescription_text,
				short_desc,
				product_sku,
		 	  	GROUP_CONCAT(DISTINCT productimage.productimage_big SEPARATOR '|') AS big_images,
		 	  	GROUP_CONCAT(DISTINCT productimage.productimage_small SEPARATOR '|') AS small_images
			FROM product
			LEFT JOIN productcategory
				ON productcategory.product_id = product.product_id
			LEFT JOIN category
				ON category.category_id = productcategory.category_id				
			LEFT JOIN productcolour 
				ON productcolour.product_id = product.product_id
			LEFT JOIN colour
				ON 	productcolour.colour_id = colour.colour_id
			LEFT JOIN plating
				ON  plating.plating_id = product.plating_id
			LEFT JOIN size
				ON 	productcolour.size_id = size.size_id
 			LEFT JOIN productimage
				ON productimage.product_id = productcolour.product_id AND productcolour.colour_id = productimage.colour_id
 			LEFT JOIN producttags
 				ON producttags.product_id = product.product_id
			LEFT JOIN tags
 				ON tags.tags_id = producttags.tags_id
 			LEFT JOIN okproductdescription
 				ON okproductdescription.product_id = product.product_id
 			LEFT JOIN okproshort_desc
 				ON okproshort_desc.product_id = product.product_id
 			WHERE 
 			    (TIMESTAMP(productcolour.productcolour_modified) BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY ) AND NOW()) AND 
 			    product.product_published = 1 AND 
 			    product.product_code > 0
 			GROUP BY productcolour.productcolour_id, productcategory.product_id
			ORDER BY productcolour.productcolour_modified ASC
 			LIMIT $limit OFFSET $offset
		");
    
	if(!empty($r))
		$q = dbAll("UPDATE importapiconfig SET import_offset = import_limit+import_offset, import_date = NOW() WHERE import_type = 'update'");
	else $q = dbAll("UPDATE importapiconfig SET import_offset = '0', import_date = NOW() WHERE import_type = 'update'");

	return $r;
}

function getModifiedproductsQty(){
	$q = dbAll("SELECT import_limit, import_offset FROM importapiconfig where import_type = 'update_qty'");
	$limit = $q[0]['import_limit'];
	$offset = $q[0]['import_offset'];
	$r = dbAll("
			SELECT 
				product.product_id, 
				product_name, 
				colour_name,
				size_name,
				productcolour_quantity as product_qty,
				plating as plating_name,
				product_sku
			FROM product
			LEFT JOIN productcategory
				ON productcategory.product_id = product.product_id
			LEFT JOIN productcolour 
				ON productcolour.product_id = product.product_id
			LEFT JOIN colour
				ON 	productcolour.colour_id = colour.colour_id
			LEFT JOIN plating
				ON  plating.plating_id = product.plating_id
			LEFT JOIN size
				ON 	productcolour.size_id = size.size_id
 			WHERE TIMESTAMP(productcolour.productcolour_modified) BETWEEN DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND NOW()
 			GROUP BY productcolour.productcolour_id, productcategory.product_id
			ORDER BY productcolour.productcolour_modified ASC
 			LIMIT $limit OFFSET $offset
		");
	if(!empty($r))
		$q = dbAll("UPDATE importapiconfig SET import_offset = import_limit+import_offset, import_date = NOW() WHERE import_type = 'update_qty'");
	else $q = dbAll("UPDATE importapiconfig SET import_offset = '0', import_date = NOW() WHERE import_type = 'update_qty'");
	return $r;
}


function getProductsforDelete(){
	$q = dbAll("SELECT import_limit, import_offset FROM importapiconfig where import_type = 'delete'");
	dbAll("UPDATE importapiconfig SET import_offset = import_limit+import_offset, import_date = NOW() WHERE import_type = 'delete'");
	return $q;
}

function getEmailByUserId1($userId)
{
	if($userId!="")
	{
	//echo "SELECT companyemail_email FROM companyemail WHERE company_id='".removeSpaces($userId)."'";die;
		$r = dbRow("SELECT companyemail_email FROM companyemail WHERE company_id='".removeSpaces($userId)."'");
		if($r!=false)
		{
			return $r['companyemail_email'];
		}	
	}	
}


function getUserIdsByEmail($email)
{
	if($email!="")
	{
		$r = dbRow("SELECT id FROM user_accounts WHERE email='".removeSpaces($email)."'");
		if($r!=false)
		{
			return $r['id'];
		}	
	}	
}

function getUserNameCodeById($invoice_customername)
{
	if($invoice_customername!="")
	{
		$invoice_customername = removeSpaces($invoice_customername);
		$r = dbRow("SELECT fname,lname,abbrv FROM user_accounts WHERE id=".$invoice_customername);
		if($r!=false)
		{
			return removeSpaces($r['fname'])." ".removeSpaces($r['lname'])."-".removeSpaces($r['abbrv']);
		}	
	}
}

/** Added By Rohan - For Plating **/

function getPlating($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT plating_id FROM product WHERE 1 AND product_id=$product_id");

		$categories = array();

			

		foreach($rs as $r)

		{

			$categories[] = $r['plating_id'];

		}

		return $categories;	

	}	

}

function getCustomerFnameLname($id)
{
	if($id!="")
	{
		$r = dbRow("select fname,lname from  user_accounts where id = '$id'");
		if($r!=false)
		{
			return $r['fname']." - ".$r['lname'];
		}
	}	
}

function getCustomerFnameLname1($id)
{
	if($id!="")
	{
		$r = dbRow("select fname,lname from  company where company_id = '$id'");
		if($r!=false)
		{
			return $r['fname']." - ".$r['lname'];
		}
	}	
}

function getCustomerNameById($invoice_customername)
{
	if($invoice_customername!="")
	{
		$invoice_customername = removeSpaces($invoice_customername);
		$r = dbRow("SELECT fname,lname,abbrv FROM user_accounts WHERE id='".$invoice_customername."'");
		if($r!=false)
		{
			return removeSpaces($r['fname'])." ".removeSpaces($r['lname']);
		}	
	}
}


function getUserAbbrvByEmail($email)
{
	if($email!="")
	{
		$email = removeSpaces($email);
		$r = dbRow("SELECT abbrv FROM user_accounts WHERE email='".$email."'");
		if($r!=false)
		{
			return $r['abbrv'];	
		}	
	}
}


function getUserIdByCode($userCode)
{
	if($userCode!="")
	{
		$userCode = removeSpaces($userCode);
		$r = dbRow("SELECT id FROM user_accounts WHERE abbrv='".$userCode."'");
		if($r!=false)
		{
			return $r['id'];	
		}	
	}
}


function getUserDiscountByCode($userCode)
{
	if($userCode!="")
	{
		$userCode = removeSpaces($userCode);
		$r = dbRow("SELECT user_discount FROM user_accounts WHERE abbrv='".$userCode."'");
		if($r!=false)
		{
			return $r['user_discount'];	
		}	
	}
}


function getUserVariantByCode($userCode)
{
	if($userCode!="")
	{
		$userCode = removeSpaces($userCode);
		$r = dbRow("SELECT uservariant_id FROM user_accounts WHERE abbrv='".$userCode."'");
		if($r!=false)
		{
			return $r['uservariant_id'];	
		}	
	}
}

/////////} End User Related Functions ////////////



/////////{ Start Order Related Functions ////////////



function getOpenOrderIdByUserId($user_id)

{

	if($user_id!="")

	{

		$r=dbRow("SELECT order_id FROM orderdetails WHERE user_id = ".removeSpaces($user_id)." AND order_type = 'order' AND order_status =1 ORDER BY order_id DESC LIMIT 1 ");

		if($r!=false)

		{

			return $r['order_id'];

		}

		else

		{

			return '-1';

		}	

	}

}



function getOrderIdByOrderNumber($order_number,$order_type='order')

{

	if($order_number!="")

	{

		$r=dbRow("SELECT order_id FROM orderdetails WHERE order_type='".$order_type."' AND order_number = '".removeSpaces($order_number)."' ORDER BY order_id DESC LIMIT 1 ");

		if($r!=false)

		{

			return $r['order_id'];

		}

	}

}



function getOpenOrderNumberByUserId($userId)

{

	if($userId!="")

	{

		$r=dbRow("SELECT order_number FROM orderdetails WHERE user_id = '".removeSpaces($userId)."' AND order_type = 'order' AND order_status =1 ORDER BY order_id DESC LIMIT 1 ");

		if($r!=false)

		{

			return $r['order_number'];

		}

	}

}

function getOrderNumberByUserId($userId)

{

	if($userId!="")

	{

		$r=dbRow("SELECT order_number FROM orderdetails WHERE user_id = '".removeSpaces($userId)."' AND order_type = 'order' ORDER BY order_id DESC LIMIT 1 ");

		if($r!=false)

		{

			return $r['order_number'];

		}

	}

}



function getOrderNumberByOrderId($orderId)

{

	if($orderId!="")

	{

		$r=dbRow("SELECT order_number FROM orderdetails WHERE order_id = ".removeSpaces($orderId)." ORDER BY order_id DESC LIMIT 1 ");

		if($r!=false)

		{

			return $r['order_number'];

		}

	}

}



function getOrderStatusByUserId($userId)

{

	if($userId!="")

	{

		

		$r=dbRow("select order_status from orderdetails where user_id='".removeSpaces($userId)."' AND order_type='order' AND order_status='1' ORDER BY order_id DESC LIMIT 1");

		

		if($r!=false)

		{

			return (int)$r['order_status'];

		}

		else

		{

			return '0';

		}	

	}

}

function getDefaultShipCompanyByUserId($userId)

{

	if($userId!="")

	{

		

		$r=dbRow("select shippingcompany_id from user_accounts where id=".removeSpaces($userId));

		

		if($r!=false)

		{

			return $r['shippingcompany_id'];

		}

		else

		{

			return '0';

		}	

	}

}

function getDefaultPaymentMethodByUserId($userId)

{

	if($userId!="")

	{

		

		$r=dbRow("select paymentmethod_id from user_accounts where id=".removeSpaces($userId));

		

		if($r!=false)

		{

			return $r['paymentmethod_id'];

		}

		else

		{

			return '0';

		}	

	}

}



function getOrderItemsAmountCount()

{

	$user_id = $_SESSION['ganesha_front_user']['id'];

	if($user_id!="")

	{

		$order_id = getOpenOrderIdByUserId($user_id);

		$orderItemSum = dbROW("SELECT SUM(orderitems_quantity) as orderitems_quantity, SUM(orderitems_amount) as orderitems_amount FROM orderitems WHERE  order_id=".$order_id);

		if($orderItemSum!=false)

		{

			return $orderItemSum['orderitems_quantity']." item(s)-".number_format($orderItemSum['orderitems_amount'],2);

		}

		else

		{

			return "0item(s)-0.00";

		}	

	}	

	else

	{

		return "0item(s)-0.00";

	}	

}



function getOrderItemsAmountSumById($order_id)

{

	if($order_id!="")

	{

		$order_id = removeSpaces($order_id);

		$r = dbRow("SELECT sum(orderitems_amount) as orderitems_amount FROM orderitems WHERE order_id=".$order_id);

		if($r!=false)

		{

			return $r['orderitems_amount'];	

		}

		else

		{

			return '0';

		}	

	}	

}



/////////} End Order Related Functions ////////////



/////////{ Start Invoice Related Functions ////////////

function getInvoiceIdByInvoiceNumber($invoice_number)

{

	if($invoice_number!="")

	{

		$invoice_number = removeSpaces($invoice_number);

		$r = dbRow("SELECT invoice_id FROM invoice WHERE invoice_number='".$invoice_number."'");

		if($r!=false)

		{

			return $r['invoice_id'];	

		}	

	}	

}



function getInvoiceStatus($invoicestatus_id)

{

	if($invoicestatus_id!="")

	{

		$invoicestatus_id = removeSpaces($invoicestatus_id);

		$r = dbRow("SELECT invoicestatus_name FROM invoicestatus WHERE invoicestatus_id=".$invoicestatus_id);

		if($r!=false)

		{

			return $r['invoicestatus_name'];	

		}	

	}	

}



function getInvoiceItemsSumById($invoice_id)

{

	if($invoice_id!="")

	{

		$invoice_id = removeSpaces($invoice_id);

		$r = dbRow("SELECT sum(invoiceitems_amount) as invoiceitems_amount FROM invoiceitems WHERE invoice_id=".$invoice_id);

		if($r!=false)

		{

			return $r['invoiceitems_amount'];	

		}	

	}	

}


function getInvoiceItemsSumQtyById($invoice_id)

{

	if($invoice_id!="")

	{

		$invoice_id = removeSpaces($invoice_id);

		$r = dbRow("SELECT sum(invoiceitems_quantity) as invoiceitems_quantity FROM invoiceitems WHERE invoice_id=".$invoice_id);

		if($r!=false)

		{

			return $r['invoiceitems_quantity'];	

		}	

	}	

}

/////////} End Invoice Related Functions ////////////

/* Added By Rohan For Purchase */

function getPurchaseIdByPurchaseNumber($grinvoice_number)
{
	if($grinvoice_number!="")
	{
		$grinvoice_number = removeSpaces($grinvoice_number);
		$r = dbRow("SELECT purchase_id FROM purchase WHERE pbill_no='".$grinvoice_number."'");
		if($r!=false)
		{
			return $r['purchase_id'];	
		}	
	}	
}





///////// Get Id's On Insert////////////

function getAvailablityIdByName($name)

{

	if($name!="")

	{	

		$r = dbRow("SELECT availability_id FROM availability WHERE availability_name='".removeSpaces($name)."'");

		return $r['availability_id'];

	}	

}



function getColourIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT colour_id FROM colour WHERE colour_name='".removeSpaces($name)."'");

		return $r['colour_id'];

	}	

}



function getCountryIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT country_id FROM country WHERE country_name='".removeSpaces($name)."'");

		return $r['country_id'];

	}	

}



function getStateIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT state_id FROM state WHERE state_name='".removeSpaces($name)."'");

		return $r['state_id'];

	}	

}



function getCityIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT city_id FROM city WHERE city_name='".removeSpaces($name)."'");

		return $r['city_id'];

	}	

}



function getColourNameById($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT colour_name FROM colour WHERE colour_id=$id");

		if($r!=false)

		{

			return $r['colour_name'];	

		}

		else

		{

			return " ";

		}	

		

	}	

}



function getColourAbbrById($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT colour_abbrv FROM colour WHERE colour_id=$id");

		if($r!=false)

		{

			return $r['colour_abbrv'];	

		}

		

	}	

}

function getProductColours($product_id)
{
	$r=dbRow("SELECT GROUP_CONCAT(DISTINCT colour.colour_name SEPARATOR ', ') as colour_name
	FROM productcolour 
	INNER JOIN colour ON productcolour.colour_id = colour.colour_id
	WHERE product_id='".removeSpaces($product_id)."' 
	GROUP BY productcolour.product_id
	ORDER BY productcolour_id ASC");
	return $r['colour_name'];
}
function getColourList($product_id)
{

	$rs=dbAll("SELECT * FROM productcolour WHERE product_id='".removeSpaces($product_id)."' order by productcolour_id ASC");

	$colors = array();

	//$bgSizeVal="";

	if(count($rs)>0)

	{

		foreach($rs as $r)

		{

			$bgSizeVal=$r['size_id'];

			

			$bgVal =  empty($bgSizeVal)?'':' ('.$bgSizeVal.')';

			

			$colors[] = ucwords(strtolower(getColourNameById($r['colour_id'])))." (".getProductSize($bgVal)."-".$r['productcolour_quantity'].")";

		}

		return join(" | ",$colors);

	}

	else

	{

		return "";

	}	

}

//////// End Get Id's On Insert /////////



///////// Get User Variant For Shopper Group////////////

function getUserVariantNameForShopperGroupList($ids)

{

	if($ids!="")

	{

		$rs = dbAll("SELECT uservariant_name FROM uservariant WHERE uservariant_id IN (".removeSpaces($ids).")");

		$uservariantName = array();

		foreach($rs as $r)

		{

			$uservariantName[] = ucwords($r['uservariant_name']); 

		}

		return implode(" | ",$uservariantName);

	}	

}



//////// End Get User Variant For Shopper Group /////////



///////// Get User Variant For Shopper Group////////////

function getUserVariantNameById($uservariant_id)

{

	if($uservariant_id!="")

	{

		$r= dbRow("SELECT uservariant_name FROM uservariant WHERE uservariant_id=".removeSpaces($uservariant_id));

		return $r['uservariant_name'];

	}	

}



//////// End Get User Variant For Shopper Group /////////



///////// Get Manufacture Category For Manufacture////////////

function getManufacCategoryNameForManufacturer($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT manufacturercategory_name FROM manufacturercategory WHERE manufacturercategory_id =".$id);

		return ucwords($r['manufacturercategory_name']);

	}	

}



//////// End Get Manufacture Category For Manufacture/////////



///////// Get Product Category Parent////////////

function getProductCategoryParent($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT category_name FROM category WHERE category_id =".$id);

		if($r['category_name']=='')

		{

			return "--";

		}	

		else

		{

			return ucwords($r['category_name']);	

		}	

	}

}



//////// End Get Product Category Parent/////////



/**{ Begins Product Related Functions **/



function getProductIdBySku($sku)  /// this function is used in invoice also

{

	if($sku!="")

	{

		$r = dbRow("SELECT product_id FROM product WHERE product_sku='$sku'");

		if($r!=false)

		{

			return $r['product_id'];

		}

		else

		{

			return $sku;

		}	

	}	

}



function getProductCodeById($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT product_code FROM product WHERE product_id=$id");

		return round($r['product_code']);

	}	

}

function getInvoiceItemSkuByItemId($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT invoiceitems_sku FROM invoiceitems WHERE invoiceitems_id=$id");

		if($r!=false)

		{

			return round($r['invoiceitems_sku']);	

		}

	}	

}



function getProductSkuById($id)

{
	$user_id = $_SESSION['userdata']['appusers_id'];
    $supplier = supplier_exist($user_id);

    if(!empty($supplier))
    {
    	$appusers_data = dbRow("select is_manufacturer_sku from appusers where appusers_id=".$user_id);
    	$is_manufacturer_sku = $appusers_data['is_manufacturer_sku'];
    }
    else
    {
    	$is_manufacturer_sku = 0;
    }

	if($id!="")

	{

		$r = dbRow("SELECT product_sku,manufacturer_product_sku FROM product WHERE product_id='$id'");

		if($r!=false)

		{
            if(empty($is_manufacturer_sku))
            {
            	return $r['product_sku'];
            }
			else
			{
				return $r['manufacturer_product_sku'];
			}
		}

		else

		{

			return $id;

		}	

	}	

}



function getProductShelfById($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT shelf_id FROM product WHERE product_id=$id");

		return $r['shelf_id'];

	}	

}



/**{ Start Shelf Related Functions **/



function getShelfNameById($shelfId)

{

	if($shelfId !="")

	{	
//echo "SELECT shelf_name FROM shelf WHERE shelf_id=$shelfId";die;
		$r = dbRow("SELECT shelf_name FROM shelf WHERE shelf_id=$shelfId");

		return $r['shelf_name'];

	}	

}



function getShelfIdByName($shelfName)

{

	if($shelfName!="")

	{

		$r = dbRow("SELECT shelf_id FROM shelf WHERE shelf_name='$shelfName'");

		return $r['shelf_id'];

	}

}

function getShelfIdByproid($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT shelf_id FROM product WHERE product_id='$id'");

		return $r['shelf_id'];

	}

}

function getsizename($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT size_name FROM size WHERE size_id='$id'");

		return $r['size_name'];

	}

}

/**} End Shelf Related Functions **/



/**{ Start Category Related Functions **/



function getCategories($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT category_id FROM productcategory WHERE 1 AND product_id=$product_id");

		$categories = array();

			

		foreach($rs as $r)

		{

			$categories[] = $r['category_id'];

		}

		return $categories;	

	}	

}



function getCategoryName($category_id)

{

	$r=dbRow("SELECT category_name FROM category WHERE 1 AND category_id=$category_id");

	if($r!=false)

	{

		return $r['category_name'];

	}	

}

/**} End Category Related Functions **/



/**{ Start Location Related Functions **/



function getLocations($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT location_id FROM productlocation WHERE 1 AND product_id=$product_id");

		$locations = array();

			

		foreach($rs as $r)

		{

			$locations[] = $r['location_id'];

		}

		return $locations;	

	}	

}



function getDefaultLocations()

{

	$r=dbRow("SELECT location_id FROM location WHERE location_default=1");

	if($r!=false)

	{

		return $r['location_id'];

	}	

}

/**} End Location Related Functions **/



/**{ Start Vat Related Functions **/

function getDefaultVat()

{

	$r=dbRow("SELECT vat_name FROM vat WHERE vat_default=1");

	if($r!=false)

	{

		return $r['vat_name'];

	}

}

/**} End Vat Related Functions **/



/**{ Start Tags Related Functions **/



function getTags($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT tags_id FROM producttags WHERE 1 AND product_id=$product_id");

		$tags = array();

			

		foreach($rs as $r)

		{

			$tags[] = $r['tags_id'];

		}

		return $tags;	

	}	

}



function getTagsName($tags_id)

{

	$r=dbRow("SELECT tags_title FROM tags WHERE 1 AND tags_id=$tags_id");

	if($r!=false)

	{

		return $r['tags_title'];

	}	

}

/**} End Tags Related Functions **/



/**{ Start shopper group Related Functions **/



function getShopperGroup($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT shoppergroup_id FROM productshoppergroup WHERE 1 AND product_id=$product_id");

		$shoppergroup = array();

			

		foreach($rs as $r)

		{

			$shoppergroup[] = $r['shoppergroup_id'];

		}

		return $shoppergroup;	

	}	

}



function getAllShopperGroup()

{

	$rs=dbAll("SELECT shoppergroup_id FROM shoppergroup WHERE 1");

	$shoppergroup = array();

	if(count($rs)>0)

	{		

		foreach($rs as $r)

		{

			$shoppergroup[] = $r['shoppergroup_id'];

		}

		return $shoppergroup;	

	}	

}



function getShopperGroupName($id)

{

	if($id!="" && !empty($id))

	{

		$r=dbRow("SELECT shoppergroup_name FROM shoppergroup WHERE shoppergroup_id=".$id);

		if($r!=false)

		{

			return $r['shoppergroup_name'];		

		}

	}	

}

/**} End shopper group Related Functions **/



/**{ Start Manufacturer Related Functions **/



 function getManufacturerIdByAbbrv($abbrv)

{

	if($abbrv!="")

	{

		$r=dbRow("SELECT manufacturer_id FROM manufacturer WHERE 1 AND manufacturer_abbrv='$abbrv'");

		return $r['manufacturer_id'];

	}	

}



function getManufacturerNameById($id)

{

	if($id!="")

	{

		$r=dbRow("SELECT manufacturer_firstname,manufacturer_lastname,manufacturer_abbrv FROM manufacturer WHERE 1 AND manufacturer_id=$id");

		// return $r['manufacturer_firstname']." ".$r['manufacturer_lastname']."-".$r['manufacturer_abbrv'];
		return $r['manufacturer_abbrv'];

	}	

}

/*
 
 function getManufacturerIdByAbbrv($abbrv)
{
	if($abbrv!="")
	{
		$r=dbRow("SELECT appusers_id FROM appusers WHERE 1 AND appusers_code='$abbrv'");
		return $r['appusers_id'];
	}	
}
function getManufacturerNameById($id)
{
	if($id!="")
	{
		$r=dbRow("SELECT appusers_firstname,appusers_lastname,appusers_code FROM appusers WHERE 1 AND appusers_id=$id");
		return $r['appusers_firstname']." ".$r['appusers_lastname']."-".$r['appusers_code'];
	}	
}

*/

/**} End Manufacturer Related Functions **/




function getAppusersupplireNameById($id)

{

	if($id!="")

	{

		$r=dbRow("SELECT appusers_firstname,appusers_lastname,appusers_code FROM appusers WHERE 1AND supplier= '1' AND appusers_id=$id");

		return $r['appusers_firstname']." ".$r['appusers_lastname']."-".$r['appusers_code'];

	}	

}


/**{ Start Description Related Functions **/



function getDescription($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT description_id FROM productdescription WHERE 1 AND product_id=$product_id");

		$description = array();

			

		foreach($rs as $r)

		{

			$description[] = $r['description_id'];

		}

		return $description;	

	}	

}



/**} End Description Related Functions **/



/**{ Start product User Variant Related Functions **/



function getUserVariant($product_id)

{

	if($product_id!="")

	{

		$rs=dbAll("SELECT uservariant_id FROM productuservariant WHERE 1 AND product_id=$product_id");

		$uservariant = array();

			

		foreach($rs as $r)

		{

			$uservariant[] = $r['uservariant_id'];

		}

		return $uservariant;	

	}	

}



/**} End product User Variant Related Functions **/



/**{ Start Divisional Factor Related Functions **/



function getDivisionalFactor()

{

	$r=dbRow("SELECT divisionfactor_value FROM divisionfactor WHERE 1 AND divisionfactor_default");

	if($r!=false)

	{

		return $r['divisionfactor_value'];

	}

	else

	{

		return '0.00';

	}		

} 



/**} End Divisional Factor Related Functions **/



/**{ Start tax Related Functions **/



function getTax()

{

	$r=dbRow("SELECT tax_id FROM tax WHERE 1 AND tax_default");

	if($r!=false)

	{

		return $r['tax_id'];

	}

} 



/**} End tax Related Functions **/



/**{ Start product size Related Functions **/



function getProductSize($sizeId)

{

	if($sizeId !="")

	{

		$r=dbRow("SELECT size_name FROM size WHERE 1 AND size_id='".removeSpaces($sizeId)."'");

		if($r!=false)

		{

			return $r['size_name'];

		}

		else

		{

			return " ";

		}	

	}		

} 



function getProductSizeIdByName($size_name)

{

	if($size_name !="")

	{

		$r=dbRow("SELECT size_id FROM size WHERE 1 AND size_name='".removeSpaces($size_name)."'");

		if($r!=false)

		{

			return $r['size_id'];

		}

		else

		{

			return "0";

		}	

	}

	else

	{

		return "0";

	}	

} 



/**} End product size Related Functions **/



/**{ Start product Whole Sale Code Related Functions **/



function getProductWholeSaleCode($id)

{

	$r=dbRow("SELECT product_code FROM product WHERE 1 AND product_id=".$id);

	if($r!=false)

	{

		return $r['product_code'];

	}

} 



/**} End product Whole Sale Code Related Functions **/



/**{ Start product Weight Related Functions **/



function getProductWeightById($id)

{

	$r=dbRow("SELECT product_weightunit,product_weight FROM product WHERE 1 AND product_id=".$id);

	if($r!=false)

	{

		return $r['product_weight']." ".$r['product_weightunit'];

	}

} 



/**} End product Weight Related Functions **/



/**{ Start product Whole Sale Rate Related Functions **/



function getProductWholeSaleRate()

{

	$r=dbRow("SELECT wholesalerate_value FROM wholesalerate WHERE wholesalerate_status ");

	if($r!=false)

	{

		return $r['wholesalerate_value'];

	}

} 



/**} End product Whole Sale Rate Related Functions **/



/**{ Start product Total Quantity Related Functions **/



function getProductTotalQtyById($product_id)

{

	$r=dbRow("SELECT sum(productcolour_quantity) as totalQty  FROM productcolour WHERE product_id=".removeSpaces($product_id));

	if($r!=false)

	{

		return $r['totalQty'];

	}

} 



/**} End product Total Quantity Related Functions **/



/**{ Start product Image Related Functions **/



function getProductImgByProductId($product_id)

{

	$r = dbRow("SELECT productimage_small FROM productimage WHERE product_id=".removeSpaces($product_id)); 

	if($r!=false)

	{	

		return $r['productimage_small'];

	}

	else

	{

		return "no_image_available.jpg";

	}	

} 

function getProductImgByProductIdAndColourId($product_id,$colour_id)

{

	$r = dbRow("SELECT productimage_small FROM productimage WHERE colour_id ='".removeSpaces($colour_id)."' AND product_id='".removeSpaces($product_id)."'"); 

	if($r !=false)

	{

		return $r['productimage_small'];

	}

	else

	{

		return "no_image_available.jpg";

	}

} 

/**} End product Image Related Functions **/



/**{ Start product Price Related Functions **/



function getProductPriceByUserVariant($product_id,$uservariant_id)

{

	$r = dbRow("SELECT productuservariant_price FROM productuservariant WHERE product_id=".removeSpaces($product_id)." AND uservariant_id=".$uservariant_id); 

	if($r!=false)

	{	

		return round($r['productuservariant_price']);

	}	

} 



function getProductMaxPriceByUserVariant($category_id,$uservariant_id)

{

	$r = dbRow("SELECT MAX(productuservariant_price) as productuservariant_price FROM productuservariant prodVariant INNER JOIN productcategory prodCtg ON prodVariant.product_id=prodCtg.product_id WHERE prodCtg.category_id=".removeSpaces($category_id)." AND prodVariant.uservariant_id=".$uservariant_id); 

	if($r!=false)

	{	

		return round($r['productuservariant_price']);

	}	

} function getProductMinPriceByUserVariant($category_id,$uservariant_id)

{

	//$r = dbRow("SELECT productuservariant_price FROM productuservariant WHERE product_id=".removeSpaces($product_id)." AND uservariant_id=".$uservariant_id); 

	$r = dbRow("SELECT MIN(productuservariant_price) as productuservariant_price  FROM productuservariant prodVariant INNER JOIN productcategory prodCtg ON prodVariant.product_id=prodCtg.product_id WHERE prodCtg.category_id=".removeSpaces($category_id)." AND prodVariant.uservariant_id=".$uservariant_id); 

	if($r!=false)

	{	

		return round($r['productuservariant_price']);

	}	

} 

/**} End product Price Related Functions **/





/**} End Product Related Functions **/



/**{ Start Functions Related To Menu **/



function getMenuIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT menu_id FROM menu WHERE menu_name='$name'");

		return $r['menu_id'];

	}	

}



function getMenuParentById($id)

{

	if($id==0 || $id=="")

	{

		return "No";

	}

	else

	{

		$query = "SELECT menu_name FROM  menu WHERE menu_id='".htmlspecialchars($id)."'"; 

		$r = dbRow($query);

		return $r['menu_name'];

	}	

}



/**} End Functions Related To Menu **/
function mailToUser($subject='',$to='',$totitle='',$from='',$fromtitle='',$replyto='',$body='',$include_path='../',$attachment='')
{
	include_once($include_path.'mailer/PHPMailerAutoload.php');
	
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = 'smtp.gmail.com';
	$mail->Port = 587;
	$mail->SMTPSecure = 'tls';
	$mail->SMTPAuth = true;
	$mail->Username = "sales@manekratna.com";
	$mail->Password = "raw2ripeblock_321"; 
	
	//Set who the message is to be sent from
	$mail->setFrom($from,$fromtitle);
	//Set an alternative reply-to address $userName
	$mail->addReplyTo($replyto, $fromtitle);
	
	$to = explode(",",$to);
	for($i=0;$i<count($to);$i++)
	{
		$mail->addAddress($to[$i],$totitle);
	}
	
	//Set whom the message is to be sent to
	//$mail->addAddress($to,$totitle);
	//$mail->addBCC('sales@manekratna.com');
	//Set the subject line
	$mail->Subject = $subject;
	$mail->msgHTML($body);
	
	if(!empty($attachment) || $attachment !='')
	{
		$mail->addAttachment($attachment); 
	}	
	 
	
	//Replace the plain text body with one created manually
	$mail->AltBody = 'This is a plain-text message body';
	if (!$mail->send())
	{
	   // echo "error|".$mail->ErrorInfo;
	   // die();
	}
	else
	{
		$mail->ClearAllRecipients();
		unset($mail);
		// echo "success|Please Check Your Email";
	}
}











///////// Start Company Details ////////////

function getCompanyName()

{

	$r = dbRow("SELECT company_name FROM companydetails LIMIT 1");

	return $r['company_name'];

}



function getTempCompanyIdByName($company_name)

{

	if($company_name!="")

	{

		$companyName = explode("|",$company_name);

		$r = dbRow("SELECT company_id FROM tempcompany WHERE company_name='".$companyName[0]."' AND company_city='".$companyName[1]."'");

		return $r['company_id'];

	}	

}



function getCompanyIdByName($company_name)

{

	if($company_name!="")

	{

		$companyName = explode("|",$company_name);

		$r = dbRow("SELECT company_id FROM company WHERE company_name='".$companyName[0]."' AND company_city='".$companyName[1]."'");

		return $r['company_id'];

	}	

}



function getCompanyCityNameById($company_id)

{

	if($company_id!="")

	{

		$r = dbRow("SELECT company_name,company_city FROM company WHERE company_id=".removeSpaces($company_id));

		return $r['company_name']."|".$r['company_city'];

	}	

}



function getInterestedInNameById($interestedin_id)

{

	if($interestedin_id!="")

	{

		$r = dbRow("SELECT interestedin_name FROM interestedin WHERE interestedin_id=".$interestedin_id);

		return $r['interestedin_name'];

	}	

}



function getCompanyNameById($company_id)

{

	if($company_id!="")

	{

		$r = dbRow("SELECT company_name FROM company WHERE company_id=".removeSpaces($company_id));

		return $r['company_name'];

	}	

}



function getZoneNameById($zone_id)

{

	if($zone_id!="")

	{

		$r = dbRow("SELECT zone_name FROM zone WHERE zone_id=".$zone_id);

		return $r['zone_name'];

	}	

}



function getAddressTypeById($id)

{

	if($id!="")

	{

		$r = dbRow("SELECT companyaddresstype_name FROM companyaddresstype WHERE companyaddresstype_id='".$id."'");

		return $r['companyaddresstype_name'];

	}	

}



function getCompanyDefaultStatus()

{

	$r = dbRow("SELECT companystatus_id FROM companystatus WHERE companystatus_default=1");

	if($r!=false)

	{

		return $r['companystatus_id'];

	}

}



function getCompanyStatus($companystatus_id)

{

	if($companystatus_id!="")

	{	

		$r = dbRow("SELECT companystatus_name FROM companystatus WHERE companystatus_id=".$companystatus_id);

		if($r!=false)

		{

			return $r['companystatus_name'];

		}

	}	

}



function getContactPersonByCompanyId($company_id, $rowLimit=0)

{

	$rowLimitVal="";

	if($rowLimit>0)

	{

		$rowLimitVal = " ORDER BY contactperson_id LIMIT ".$rowLimit;

	}	

	$Rs = dbAll("SELECT contactperson_name FROM contactperson WHERE company_id=$company_id ".$rowLimitVal);

	if($Rs!=false && count($Rs)>0)

	{

		$contactPerson=array();

		foreach($Rs as $R)

		{

			$contactPerson[]=$R['contactperson_name'];

		}

		return implode(", ",$contactPerson);

	}

}



function getExibitYearById($year_id)

{

	$Rs = dbAll("SELECT contactperson_name FROM contactperson WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$contactPerson=array();

		foreach($Rs as $R)

		{

			$contactPerson[]=$R['contactperson_name'];

		}

		return implode(", ",$contactPerson);

	}

}



function getContactPersonEmailByContactPersonId($contactperson_id)

{

	if($contactperson_id!="")

	{

		$Rs = dbAll("SELECT contactpersonemail_email FROM contactpersonemail WHERE contactperson_id=".removeSpaces($contactperson_id));

		if($Rs!=false && count($Rs)>0)

		{

			$contactPersonEmail=array();

			foreach($Rs as $R)

			{

				$contactPersonEmail[]=$R['contactpersonemail_email'];

			}

			return implode(", ",$contactPersonEmail);

		}	

	}

}



function getContactPersonMobileByContactPersonId($contactperson_id)

{

	if($contactperson_id!="")

	{

		$Rs = dbAll("SELECT contactpersonmobile_mobile FROM contactpersonmobile WHERE contactperson_id=".removeSpaces($contactperson_id));

		if($Rs!=false && count($Rs)>0)

		{

			$contactPersonMobile=array();

			foreach($Rs as $R)

			{

				$contactPersonMobile[]=$R['contactpersonmobile_mobile'];

			}

			return implode(", ",$contactPersonMobile);

		}	

	}

}



function getContactPersonMobileByCompanyId($company_id,$rowLimit=0)

{

	if($rowLimit>0)

	{

		$rowLimitVal = " ORDER BY contactperson_id LIMIT ".$rowLimit;

	}	

	

	if($company_id!="")

	{

		$Rs = dbAll("SELECT contactpersonmobile_mobile FROM contactpersonmobile WHERE company_id=".removeSpaces($company_id)." ".$rowLimitVal);

		if($Rs!=false && count($Rs)>0)

		{

			$contactPersonMobile=array();

			foreach($Rs as $R)

			{

				$contactPersonMobile[]=$R['contactpersonmobile_mobile'];

			}

			return implode(", ",$contactPersonMobile);

		}	

	}

}



function getTempContactPersonIdByCompanyIdCntName($company_id,$contactperson_name)

{

	if($company_id!="" && $contactperson_name!="")

	{

		$r = dbRow("SELECT contactperson_id FROM tempcontactperson WHERE company_id=".removeSpaces($company_id)." AND contactperson_name='".removeSpaces($contactperson_name)."'");

		if($r!=false)

		{

			return $r['contactperson_id'];

		}	

	}

}



function getContactPersonIdByCompanyIdCntName($company_id,$contactperson_name)

{

	if($company_id!="" && $contactperson_name!="")

	{

		$r = dbRow("SELECT contactperson_id FROM contactperson WHERE company_id=".removeSpaces($company_id)." AND contactperson_name='".removeSpaces($contactperson_name)."'");

		if($r!=false)

		{

			return $r['contactperson_id'];

		}	

	}

}



function getCountryByCompanyId($company_id)
{
	$r = dbRow("SELECT company_country FROM company WHERE company_id=".$company_id);
	if($r != false)
	{
		return $r['company_country'];
	}	
}
function getCountryCityByCompanyId($company_id)
{

	$Rs = dbAll("SELECT companyaddress_country,companyaddress_city FROM companyaddress WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$countryCity=array();

		foreach($Rs as $R)

		{

			$contactPerson[]=$R['companyaddress_country'].", ".$R['companyaddress_city'];

		}

		return implode("<br> ",$contactPerson);

	}

}



function getCompanyContactNumber($company_id)

{

	$Rs = dbAll("SELECT companycontact_contact FROM companycontact WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$companyContact=array();

		foreach($Rs as $R)

		{

			$companyContact[]=$R['companycontact_contact'];

		}

		return implode(", ",$companyContact);

	}

}



function getTempCompanyContactNumber($company_id)

{

	$Rs = dbAll("SELECT companycontact_contact FROM tempcompanycontact WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$companyContact=array();

		foreach($Rs as $R)

		{

			$companyContact[]=$R['companycontact_contact'];

		}

		return implode(", ",$companyContact);

	}

}



function getCompanyEmailId($company_id)

{

	$Rs = dbAll("SELECT companyemail_email FROM companyemail WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$companyEmail=array();

		foreach($Rs as $R)

		{

			$companyEmail[]=$R['companyemail_email'];

		}

		return implode(", ",$companyEmail);

	}

}



function getTempCompanyEmailId($company_id)

{

	$Rs = dbAll("SELECT companyemail_email FROM tempcompanyemail WHERE company_id=$company_id");

	if($Rs!=false && count($Rs)>0)

	{

		$companyEmail=array();

		foreach($Rs as $R)

		{

			$companyEmail[]=$R['companyemail_email'];

		}

		return implode(", ",$companyEmail);

	}

}

///////// End Company Details ////////////



///////// Get Id's On Insert////////////



function getCompanySourceId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companysource_id FROM company_companysource WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companySourceId=array();

			foreach($Rs as $R)

			{

				$companySourceId[]=$R['companysource_id'];

			}

			return implode(",",$companySourceId);

		}

	}

}



function getCompanyProductId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companyproduct_id FROM company_companyproduct WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyProductId=array();

			foreach($Rs as $R)

			{

				$companyProductId[]=$R['companyproduct_id'];

			}

			return implode(",",$companyProductId);

		}

	}

}



function getCompanySegmentId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companysegment_id FROM company_companysegment WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companySegmentId=array();

			foreach($Rs as $R)

			{

				$companySegmentId[]=$R['companysegment_id'];

			}

			return implode(",",$companySegmentId);

		}

	}

}



function getCompanyBussinessCategoryId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companybusinesscategory_id FROM company_companybusinesscategory WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyBussinessCategoryId=array();

			foreach($Rs as $R)

			{

				$companyBussinessCategoryId[]=$R['companybusinesscategory_id'];

			}

			return implode(",",$companyBussinessCategoryId);

		}

	}

}



function getCompanyBussinessCategoryIdForCsv($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companybusinesscategory_id FROM company_companybusinesscategory WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyBussinessCategoryName=array();

			foreach($Rs as $R)

			{

				$companyBussinessCategoryName[]=getCompanyBusinessCategoryName($R['companybusinesscategory_id']);

			}

			return implode(" / ",$companyBussinessCategoryName);

		}

	}

}



function getCompanyGeneratedById($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT appusers_id FROM company_companygeneratedby WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyGeneratedById=array();

			foreach($Rs as $R)

			{

				$companyGeneratedById[]=$R['appusers_id'];

			}

			return implode(",",$companyGeneratedById);

		}

	}

}



function getCompanyVisitYearId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companyyear_id FROM company_companyyear WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyYearId=array();

			foreach($Rs as $R)

			{

				$companyYearId[]=$R['companyyear_id'];

			}

			return implode(",",$companyYearId);

		}

	}

}



function getCompanyExhibitYearId($company_id)

{

	if($company_id!="")

	{

		$Rs = dbAll("SELECT companyyear_id FROM company_companyexhibityear WHERE company_id=$company_id");

		if($Rs!=false && count($Rs)>0)

		{

			$companyYearId=array();

			foreach($Rs as $R)

			{

				$companyYearId[]=$R['companyyear_id'];

			}

			return implode(",",$companyYearId);

		}

	}

}



function getSourceNameById($id)

{

	if($id!="")

	{	

		$r = dbRow("SELECT companysource_name FROM companysource WHERE companysource_id='".removeSpaces($id)."'");

		return $r['companysource_name'];

	}	

}



function getSuburbIdByName($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT suburb_id FROM suburb WHERE suburb_name='".removeSpaces($name)."'");

		return $r['suburb_id'];

	}	

}



function getCompanyBusinessCategoryName($companybusinesscategory_id)

{

	if($companybusinesscategory_id!="")

	{

		$r=dbRow("SELECT companybusinesscategory_name FROM companybusinesscategory WHERE 1 AND companybusinesscategory_id=$companybusinesscategory_id");

		if($r!=false)

		{

			return $r['companybusinesscategory_name'];

		}

		else

		{

			return "---";

		}

	}	

}



function getCompanySegmentName($companysegment_id)

{

	if($companysegment_id!="")

	{

		$r=dbRow("SELECT companysegment_name FROM companysegment WHERE 1 AND companysegment_id=$companysegment_id");

		if($r!=false)

		{

			return $r['companysegment_name'];

		}

		else

		{

			return "---";

		}

	}

	else

	{

		return "---";

	}

}



function getUserIdByCode1($code)

{

	if($code!="")

	{

		$r = dbRow("SELECT appusers_id FROM appusers WHERE appusers_code='".htmlspecialchars($code)."'"); 

		if($r != false)

		{

			return $r['appusers_id'];

		}

	}	

}



function getUserNameCodeById1($appusers_id)

{

	if($appusers_id!="")

	{

		$r = dbRow("SELECT appusers_firstname,appusers_lastname,appusers_code FROM appusers WHERE appusers_id='".htmlspecialchars($appusers_id)."'"); 

		if($r != false)

		{

			return $r['appusers_firstname']." ".$r['appusers_lastname']."-".$r['appusers_code'];

		}

		else

		{

			return "-";

		}

	}	

}



function getUserNameById($appusers_id)

{

	if($appusers_id!="")

	{

		$r = dbRow("SELECT appusers_firstname,appusers_lastname FROM appusers WHERE appusers_id='".removeSpaces($appusers_id)."'"); 

		if($r != false)

		{

			return $r['appusers_firstname']." ".$r['appusers_lastname'];

		}

	}	

}



function getConcernPersonByActvityId($activity_id)

{

	if($activity_id!="")

	{

		$r = dbRow("SELECT 	minutes_person FROM minutes WHERE activity_id=$activity_id");

		if($r!=false)

		{

			return $r['minutes_person'];	

		}		

	}	

}



function getMinutesIdByActvityId($activity_id)

{

	if($activity_id!="")

	{

		$r = dbRow("SELECT minutes_id FROM minutes WHERE activity_id=$activity_id");

		if($r!=false)

		{

			return $r['minutes_id'];	

		}

		else

		{

			return -1;

		}	

		

	}	

}



/**{ Start Activity Related Functions **/



function getDefaultActivityStatus()

{

	$r=dbRow("SELECT activitystatus_name FROM activitystatus WHERE 1 AND activitystatus_default");

	if($r!=false)

	{

		return $r['activitystatus_name'];

	}

} 



function getCompanyNameCodeById($invoice_customername)

{

	if($invoice_customername!="")

	{

		$invoice_customername = removeSpaces($invoice_customername);

		$r = dbRow("SELECT fname,lname,abbrv FROM company WHERE company_id=".$invoice_customername);

		if($r!=false)

		{

			return removeSpaces($r['fname'])." ".removeSpaces($r['lname'])."-".removeSpaces($r['abbrv']);

		}	

	}

}



function getCompanyDiscountByCode($userCode)

{

	if($userCode!="")

	{

		$userCode = removeSpaces($userCode);

		$r = dbRow("SELECT company_discount FROM company WHERE abbrv='".$userCode."'");

		if($r!=false)

		{

			return $r['company_discount'];	

		}	

	}

}



function getcompanyIdByCode($userCode)

{

	if($userCode!="")

	{

		$userCode = removeSpaces($userCode);

		//echo "SELECT company_id FROM company WHERE abbrv='".$userCode."'";die;

		$r = dbRow("SELECT company_id FROM company WHERE abbrv='".$userCode."'");

		if($r!=false)

		{

			return $r['company_id'];	

		}	

	}

}



function getCompanyVariantByCode($userCode)

{

	if($userCode!="")

	{

		$userCode = removeSpaces($userCode);

		//echo "SELECT uservariant_id FROM company WHERE abbrv='".$userCode."'";die;

		$r = dbRow("SELECT uservariant_id FROM company WHERE abbrv='".$userCode."'");

		if($r!=false)

		{

			return $r['uservariant_id'];	

		}	

	}

}



function getDefaultShipCompanyBycomId($userId)

{

	if($userId!="")

	{

		

		$r=dbRow("select shippingcompany_id from company where company_id=".removeSpaces($userId));

		

		if($r!=false)

		{

			return $r['shippingcompany_id'];

		}

		else

		{

			return '0';

		}	

	}

}

function getDefaultPaymentMethodBycompanyId($userId)

{

	if($userId!="")

	{

		

		$r=dbRow("select paymentmethod_id from company where company_id=".removeSpaces($userId));

		

		if($r!=false)

		{

			return $r['paymentmethod_id'];

		}

		else

		{

			return '0';

		}	

	}

}



function getProductName($proid)

{

	if($proid!="")

	{

		$r = dbRow("SELECT product_name FROM product WHERE product_id='".removeSpaces($proid)."'");

		if($r!=false)

		{

			return $r['product_name'];

		}	

	}	

	

}



function getProductImage($proid)

{

	if($proid!="")

	{

		$r = dbRow("SELECT productimage_small FROM productimage WHERE product_id='".removeSpaces($proid)."'");

		if($r!=false)

		{

			return $r['productimage_small'];

		}	

	}	

	

}

function getCompanyId($company_name)

{

	if($company_name!="")

	{

		//$companyName = explode("|",$company_name);

		//echo $company_name;die;

		//echo "SELECT company_id FROM company WHERE company_name='".$company_name."'";die;

		$r = dbRow("SELECT company_id FROM company WHERE company_name='".$company_name."'");

		return $r['company_id'];

	}	

}



function getInvoiceUserid($invoice)

{

	if($invoice!="")

	{

		$invoice_id = removeSpaces($invoice);

		$r = dbRow("SELECT invoice_customername FROM invoice WHERE invoice_id=".$invoice);

		if($r!=false)

		{

			return $r['invoice_customername'];	

		}	

	}	

}





function getaccessidbynme($name)

{

	if($name!="")

	{

		$r = dbRow("SELECT access_id FROM access_tbl WHERE 	access_name='".removeSpaces($name)."'");

		if($r!=false)

		{

			return $r['access_id'];

		}	

	}	

	

}



function getaccessnamebyid($id)

{

	if($id!="")

	{

		//echo "SELECT access_name FROM access_tbl WHERE access_id='".removeSpaces($id)."'";

		$r = dbRow("SELECT access_name FROM access_tbl WHERE access_id='".removeSpaces($id)."'");

		if($r!=false)

		{

			return $r['access_name'];

		}	

	}	

	

}





///////// Start Site Menu baar ////////////

function getMenu()

{  

	$r = dbAll("select * from access_tbl where access_parent='0' AND access_published='1'");

	return $r;

	//print_r($r);die;

}



function getsubcategory($id)

{

    // echo "SELECT access_id FROM access_tbl WHERE access_parent='".$id."' AND access_published='1'";die;

	$r = dbQuery1("SELECT access_id FROM access_tbl WHERE access_parent='".$id."' AND access_published='1'");

	return $r;

	

}



function getsubcategory1($id)

{

     echo "SELECT access_id FROM access_tbl WHERE access_parent='".$id."' AND access_published='1'";die;

	$r = dbQuery1("SELECT access_id FROM access_tbl WHERE access_parent='".$id."' AND access_published='1'");

	return $r;

	

}



function getchildcategory($id)

{  

	$r = dbAll("SELECT * FROM access_tbl WHERE access_parent='".removeSpaces($id)."' AND access_published='1'");

	return $r;

	//print_r($r);die;

}



function getsubchild($id)

{  

	$r = dbAll("SELECT * FROM access_tbl WHERE access_parent='".removeSpaces($id)."' AND access_published='1'");

	return $r;

	//print_r($r);die;

}



function getsubsubchild($id)

{  

	$r = dbAll("SELECT * FROM access_tbl WHERE access_parent='".removeSpaces($id)."' AND access_published='1'");

	return $r;

	//print_r($r);die;

}





function getgroupidbynme($name)

{

	if($name!="")

	{

	

		$r = dbRow("SELECT group_id FROM access_grouptbl WHERE 	group_name='".removeSpaces($name)."'");

		if($r!=false)

		{

			return $r['group_id'];

		}	

	}	

	

}


/**{ Start User Varient Related Functions **/

function getUserVarient($product_id)
{
	if($product_id!="")
	{
		$rs=dbAll("SELECT uservariant_id FROM productuservariant WHERE 1 AND product_id=$product_id");
		$shoppergroup = array();
			
		foreach($rs as $r)
		{
			$shoppergroup[] = $r['uservariant_id'];
		}
		return $shoppergroup;	
	}	
}

function getAllUserVarient()
{
//echo "SELECT uservariant_id FROM uservariant WHERE 1";die;
	$rs=dbAll("SELECT uservariant_id FROM uservariant WHERE 1");
	$uservariant = array();
	if(count($rs)>0)
	{		
		foreach($rs as $r)
		{
			$uservariant[] = $r['uservariant_id'];
		}
		return $uservariant;	
		//print_r($uservariant);die;
	}	
}

function getUserVarientName($id)
{
	if($id!="" && !empty($id))
	{
		$r=dbRow("SELECT uservariant_name FROM uservariant WHERE uservariant_id=".$id);
		if($r!=false)
		{
			return $r['uservariant_name'];		
		}
	}	
}

function getcompanyUserVarientid($id)
{
	if($id!="" && !empty($id))
	{
		$r=dbRow("SELECT uservariant_id FROM company WHERE company_id=".$id);
		if($r!=false)
		{
			return $r['uservariant_id'];		
		}
	}	
}

function getcompanyEmailByUserId($userId)
{
	if($userId!="")
	{
		$r = dbRow("SELECT companyemail_email FROM companyemail WHERE company_id='".removeSpaces($userId)."'");
		if($r!=false)
		{
			return $r['companyemail_email'];
		}	
	}	
	
}


function getcompanyCustomerFnameLname($id)
{
	if($id!="")
	{
		$r = dbRow("select fname,lname from  company where company_id = '$id'");
		if($r!=false)
		{
			return $r['fname']." - ".$r['lname'];
		}
	}	
}

function supplier_exist($user_id)

{

	$r=dbRow("SELECT supplier FROM appusers WHERE appusers_id = '$user_id'");

	if($r!=false)

	{

		return $r['supplier'];

	}

} 

function getorderuserid($order_id)

{

	$r=dbRow("SELECT user_id FROM orderdetails WHERE order_id = '$order_id'");

	if($r!=false)

	{

		return $r['user_id'];

	}

}

function getorderstatusneame($status_id)

{

	$r=dbRow("SELECT orderstatus_name FROM orderstatus WHERE orderstatus_value = '$status_id'");

	if($r!=false)

	{

		return $r['orderstatus_name'];

	}

}

function getinvoicestatusneame($status_id)

{

	$r=dbRow("SELECT invoicestatus_name FROM invoicestatus WHERE 	invoicestatus_id = '$status_id'");

	if($r!=false)

	{

		return $r['invoicestatus_name'];

	}

}


function getorderactiontype($order_id)

{

	$r=dbRow("SELECT order_act FROM orderdetails WHERE order_id = '$order_id'");

	if($r!=false)

	{

		return $r['order_act'];

	}

}

function getordertype($order_id)

{

	$r=dbRow("SELECT order_type FROM orderdetails WHERE order_id = '$order_id'");

	if($r!=false)

	{

		return $r['order_type'];

	}

}



function GetCartId()

{

// This function will generate an encrypted string and

// will set it as a cookie using set_cookie. This will

// also be used as the cookieId field in the cart table



if(isset($_COOKIE["cartId"]))

{

return $_COOKIE["cartId"];

}

else

{

// There is no cookie set. We will set the cookie

// and return the value of the users session ID



session_start();

setcookie("cartId", session_id(), time() + ((3600 * 24) * 365));

return session_id();

}

}





function cartcookie_exist($id)

{

	//echo "SELECT * FROM cart WHERE cuser_id='".$id."'";

   	$r = dbAll("SELECT * FROM cart WHERE cuser_id='".$id."'");

	return $r;

	

}
function autologin(){
	// Check if the cookie exists
if(isset($_COOKIE["ID_my_site"]))
	{
	
	// parse_str($_COOKIE["ID_my_site"]);
	// echo "SELECT appusers_id FROM appusers WHERE appusers_code='".removeSpaces($_COOKIE["ID_my_site"])."'";die;
		$r = dbRow("SELECT appusers_id FROM appusers WHERE appusers_code='".removeSpaces($_COOKIE["ID_my_site"])."'");
		if($r!=false)
		{
			return $r['appusers_id'];
		}	
	}
}

/* Added By Rohan to send backend OTP */

function sendSms($mobile_no,$msg)
{
	/*
		Userid - info.smeservices
		Password - vidhi262729
	*/

	$postUrl = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php";
	
	$postData = array(
							'username' => 'MANRAT',
							'password' => 'raw2ripe_321',
							'sender' => 'MANRAT',
							'to' => $mobile_no,
							'message' => $msg,
							'reqid' => 1,
							'format' => 'text',
							'unique' => 0
						);
	$handle = curl_init($postUrl);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($handle, CURLOPT_POST, true);
	curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
	$output = curl_exec($handle);
	curl_close($handle);
}


/* Added By Rohan For Role Base Access */

// get group base access rights

	function getGroupAccessByName($groups)
	{
		$r_group = dbRow('select * from access_grouptbl where group_name="'.$groups.'" and group_status="1"');
		$r_access = dbAll('select * from access_tbl');
		$group_access = array();

		if(!empty($r_access) && $r_group)
		{
			foreach ($r_access as $val) 
			{
				$access_arr = json_decode($val['access_mapping'],true);

				if(!empty($access_arr))
				{
					foreach ($access_arr as $key_access=>$val_access) 
					{
						if($key_access==$r_group['group_id'])
						{
							// $group_access[$i]['id'] = $val['access_id'];
							$access_level = array();
							
							foreach($val_access as $access_section_key=>$access_section)
							{
								if(!empty($access_section))
								{
									$access_level[] = $access_section_key;
								}
								
							}

							$group_access[$val['access_name']] = $access_level;

							if(!empty($val['access_link']))
							{
								$group_access[$val['access_link']] = $access_level;
							}
						}
					}
				}
			}
			
		}

		return $group_access;
	}

	function loadAccessFiles($group_access,$script_name)
	{
		//print_r($script_name);
		if(in_array('edit', $group_access[$script_name]))
		{
			$is_edit = 'Y';
		}
		else
		{
			$is_edit = 'N';
		}
		
		if(in_array('delete', $group_access[$script_name]))
		{
			$is_delete = 'Y';
		}
		else
		{
			$is_delete = 'N';
		}

		if(in_array('view', $group_access[$script_name]))
		{
			$is_view = 'Y';
		}

		if(in_array('new', $group_access[$script_name]))
		{
			$is_new = 'Y';
		}

		$module = str_replace(".php", "", $script_name);
		
		if(!empty($group_access[$script_name]) && count($group_access[$script_name])==4)
		{
			if(isset($_REQUEST['id']))
			{
				if($module=='masters/manufacturercategory')
				{
					require("manufacturecategory/form.php");
				}
				else
				{
					require(str_replace("masters/", "", $module)."/form.php");
				}
			}
			else
			{
				if($module=='manageuser1')
				{
					require($module."/userList.php");
				}
				else if($module=='barcodelist')
				{
					require($module."/barcodeList.php");
				}
				else if($module=='exporter')
				{
					require($module."/companyList.php");
				}
				else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="sale"))
				{
					require($module."/sLedgerList.php");
				}
				else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="purchase"))
				{
					require($module."/pLedgerList.php");
				}
				else if($module=='masters/category')
				{
					require("category/categoryList.php");
				}
				else if($module=='masters/location')
				{
					require("location/locationList.php");
				}
				else if($module=='masters/manufacturer')
				{
					require("manufacturer/manufacturerList.php");
				}
				else if($module=='masters/manufacturercategory')
				{
					require("manufacturecategory/manufacturercategoryList.php");
				}
				else if($module=='masters/tags')
				{
					require("tags/tagsList.php");
				}
				else if($module=='masters/shoppergroup')
				{
					require("shoppergroup/shoppergroupList.php");
				}
				else if($module=='masters/uservariant')
				{
					require("uservariant/uservariantList.php");
				}
				else if($module=='masters/shelf')
				{
					require("shelf/shelfList.php");
				}
				else if($module=='masters/colour')
				{
					require("colour/colourList.php");
				}
				else if($module=='masters/size')
				{
					require("size/sizeList.php");
				}
				else if($module=='masters/description')
				{
					require("description/descriptionList.php");
				}
				else if($module=='masters/divisionfactor')
				{
					require("divisionfactor/divisionfactorList.php");
				}
				else if($module=='masters/discount')
				{
					require("discount/discountList.php");
				}
				else if($module=='masters/tax')
				{
					require("tax/taxList.php");
				}
				else if($module=='masters/dimensionandunit')
				{
					require("dimensionandunit/dimensionandunitList.php");
				}
				else if($module=='masters/availability')
				{
					require("availability/availabilityList.php");
				}
				else if($module=='masters/wholesalerate')
				{
					require("wholesalerate/wholesalerateList.php");
				}
				else if($module=='masters/companycategory')
				{
					require("companycategory/companycategoryList.php");
				}
				else if($module=='masters/companybusinesscategory')
				{
					require("companybusinesscategory/companybusinesscategoryList.php");
				}
				else if($module=='masters/companyproduct')
				{
					require("companyproduct/companyproductList.php");
				}
				else if($module=='masters/interestedin')
				{
					require("interestedin/interestedinList.php");
				}
				else if($module=='masters/companysource')
				{
					require("companysource/companysourceList.php");
				}
				else if($module=='masters/companyyear')
				{
					require("companyyear/companyyearList.php");
				}
				else if($module=='masters/zone')
				{
					require("zone/zoneList.php");
				}
				else if($module=='masters/country')
				{
					require("country/countryList.php");
				}
				else if($module=='masters/state')
				{
					require("state/stateList.php");
				}
				else if($module=='masters/plating')
				{
					require("plating/platingList.php");
				}
				else if($module=='masters/city')
				{
					require("city/cityList.php");
				}
				else if($module=='masters/suburb')
				{
					require("suburb/suburbList.php");
				}
				else if($module=='masters/companystatus')
				{
					require("companystatus/companystatusList.php");
				}
				else if($module=='masters/companyaddresstype')
				{
					require("companyaddresstype/companyaddresstypeList.php");
				}
				else
				{
					require($module."/".str_replace("list", "", $module)."List.php");
				}
				
			}
		}
		else if(!empty($group_access[$script_name]) && count($group_access[$script_name])<4)
		{
			if(($is_new=='Y' || $is_edit=='Y') && $is_view=='Y')
			{
				if(isset($_REQUEST['id']))
				{
					if($module=='labellist')
					{
						require("companylabel/form.php");
					}
					if($module=='manufacturercategory')
					{
						require("manufacturecategory/form.php");
					}
					else
					{
						require($module."/form.php");
					}
				}
				else
				{
					if($module=='manageuser1')
					{
						require($module."/userList.php");
					}
					else if($module=='uploadimage')
					{
						require($module."/uploadimage.php");
					}
					else if($module=='barcodelist')
    				{
    					require($module."/barcodeList.php");
    				}
					else if($module=='labellist')
					{
						require($module."/companylabelList.php");
					}
					else if($module=='exporter')
					{
						require($module."/companyList.php");
					}
					else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="sale"))
					{
						require($module."/sLedgerList.php");
					}
					else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="purchase"))
					{
						require($module."/pLedgerList.php");
					}
					else if($module=='masters/category')
					{
						require("category/categoryList.php");
					}
					else if($module=='masters/location')
					{
						require("location/locationList.php");
					}
					else if($module=='masters/manufacturer')
					{
						require("manufacturer/manufacturerList.php");
					}
					else if($module=='masters/manufacturercategory')
					{
						require("manufacturecategory/manufacturercategoryList.php");
					}
					else if($module=='masters/tags')
					{
						require("tags/tagsList.php");
					}
					else if($module=='masters/plating')
					{
						require("plating/platingList.php");
					}
					else if($module=='masters/shoppergroup')
					{
						require("shoppergroup/shoppergroupList.php");
					}
					else if($module=='masters/uservariant')
					{
						require("uservariant/uservariantList.php");
					}
					else if($module=='masters/shelf')
					{
						require("shelf/shelfList.php");
					}
					else if($module=='masters/colour')
					{
						require("colour/colourList.php");
					}
					else if($module=='masters/size')
					{
						require("size/sizeList.php");
					}
					else if($module=='masters/description')
					{
						require("description/descriptionList.php");
					}
					else if($module=='masters/divisionfactor')
					{
						require("divisionfactor/divisionfactorList.php");
					}
					else if($module=='masters/discount')
	    			{
	    				require("discount/discountList.php");
	    			}
	    			else if($module=='masters/tax')
	    			{
	    				require("tax/taxList.php");
	    			}
	    			else if($module=='masters/dimensionandunit')
	    			{
	    				require("dimensionandunit/dimensionandunitList.php");
	    			}
	    			else if($module=='masters/availability')
	    			{
	    				require("availability/availabilityList.php");
	    			}
	    			else if($module=='masters/wholesalerate')
	    			{
	    				require("wholesalerate/wholesalerateList.php");
	    			}
	    			else if($module=='masters/companycategory')
					{
						require("companycategory/companycategoryList.php");
					}
					else if($module=='masters/companybusinesscategory')
					{
						require("companybusinesscategory/companybusinesscategoryList.php");
					}
					else if($module=='masters/companyproduct')
					{
						require("companyproduct/companyproductList.php");
					}
					else if($module=='masters/interestedin')
					{
						require("interestedin/interestedinList.php");
					}
					else if($module=='masters/companysource')
					{
						require("companysource/companysourceList.php");
					}
					else if($module=='masters/companyyear')
					{
						require("companyyear/companyyearList.php");
					}
					else if($module=='masters/zone')
					{
						require("zone/zoneList.php");
					}
					else if($module=='masters/country')
					{
						require("country/countryList.php");
					}
					else if($module=='masters/state')
					{
						require("state/stateList.php");
					}
					else if($module=='masters/city')
					{
						require("city/cityList.php");
					}
					else if($module=='masters/suburb')
					{
						require("suburb/suburbList.php");
					}
					else if($module=='masters/companystatus')
					{
						require("companystatus/companystatusList.php");
					}
					else if($module=='masters/companyaddresstype')
					{
						require("companyaddresstype/companyaddresstypeList.php");
					}
					else
					{
						require($module."/".$module."List.php");
					}
				}
			}
			else if($is_view=='Y')
			{
				if($module=='manageuser1')
				{
					require($module."/userList.php");
				}
				else if($module=='barcodelist')
				{
					require($module."/barcodeList.php");
				}
				else if($module=='labellist')
				{
					require($module."/companylabelList.php");
				}
				else if($module=='exporter')
				{
					require($module."/companyList.php");
				}
				else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="sale"))
				{
					require($module."/sLedgerList.php");
				}
				else if($module=='ledger' && (!empty($_REQUEST['ledger_type']) && $_REQUEST['ledger_type']=="purchase"))
				{
					require($module."/pLedgerList.php");
				}
				else if($module=='masters/category')
				{
					require("category/categoryList.php");
				}
				else if($module=='masters/location')
				{
					require("location/locationList.php");
				}
				else if($module=='masters/manufacturer')
				{
					require("manufacturer/manufacturerList.php");
				}
				else if($module=='masters/manufacturercategory')
				{
					require("manufacturecategory/manufacturercategoryList.php");
				}
				else if($module=='masters/tags')
				{
					require("tags/tagsList.php");
				}
				else if($module=='masters/shoppergroup')
				{
					require("shoppergroup/shoppergroupList.php");
				}
				else if($module=='masters/uservariant')
				{
					require("uservariant/uservariantList.php");
				}
				else if($module=='masters/shelf')
				{
					require("shelf/shelfList.php");
				}
				else if($module=='masters/colour')
				{
					require("colour/colourList.php");
				}
				else if($module=='masters/size')
				{
					require("size/sizeList.php");
				}
				else if($module=='masters/description')
				{
					require("description/descriptionList.php");
				}
				else if($module=='masters/divisionfactor')
				{
					require("divisionfactor/divisionfactorList.php");
				}
				else if($module=='masters/discount')
				{
					require("discount/discountList.php");
				}
				else if($module=='masters/tax')
				{
					require("tax/taxList.php");
				}
				else if($module=='masters/dimensionandunit')
				{
					require("dimensionandunit/dimensionandunitList.php");
				}
				else if($module=='masters/availability')
				{
					require("availability/availabilityList.php");
				}
				else if($module=='masters/wholesalerate')
				{
					require("wholesalerate/wholesalerateList.php");
				}
				else if($module=='masters/companycategory')
				{
					require("companycategory/companycategoryList.php");
				}
				else if($module=='masters/companybusinesscategory')
				{
					require("companybusinesscategory/companybusinesscategoryList.php");
				}
				else if($module=='masters/companyproduct')
				{
					require("companyproduct/companyproductList.php");
				}
				else if($module=='masters/interestedin')
				{
					require("interestedin/interestedinList.php");
				}
				else if($module=='masters/companysource')
				{
					require("companysource/companysourceList.php");
				}
				else if($module=='masters/companyyear')
				{
					require("companyyear/companyyearList.php");
				}
				else if($module=='masters/zone')
				{
					require("zone/zoneList.php");
				}
				else if($module=='masters/country')
				{
					require("country/countryList.php");
				}
				else if($module=='masters/state')
				{
					require("state/stateList.php");
				}
				else if($module=='masters/city')
				{
					require("city/cityList.php");
				}
				else if($module=='masters/suburb')
				{
					require("suburb/suburbList.php");
				}
				else if($module=='masters/companystatus')
				{
					require("companystatus/companystatusList.php");
				}
				else if($module=='masters/companyaddresstype')
				{
					require("companyaddresstype/companyaddresstypeList.php");
				}
				else
				{
					require($module."/".$module."List.php");
				}
			}
		}
		else
		{
			echo "You don't have permission to access this page";
		}
	}


/**} End Activity Related Functions **/

/* 



function getOldPassword($userId)

{

	$emailQuery = "SELECT password FROM user_accounts WHERE id='".htmlspecialchars($userId)."'"; 

	$r = dbRow($emailQuery);

	return $r['password'];

}



function getShipIdByUserId($userId)

{

	$r = dbRow($email);

	return $r['id'];

}



function getCategoryById($id)

{

	$categoryNameQuery = "SELECT cname FROM  pcategory WHERE id='".htmlspecialchars($id)."'"; 

	$r = dbRow($categoryNameQuery);

	return $r['cname'];

}



function getProductByCode($code)

{

	$productIdQuery = "SELECT id FROM stock WHERE code='".htmlspecialchars($code)."'"; 

	$r = dbRow($productIdQuery);

	return $r['id'];

}



function getShelfById($id)

{

	$shelfNameQuery = "SELECT shelf_name FROM  shelf WHERE id='".htmlspecialchars($id)."'"; 

	$r = dbRow($shelfNameQuery);

	return $r['shelf_name'];

}



function getShelfIdByName($name)

{

	$shelfNameQuery = "SELECT id FROM  shelf WHERE shelf_name='".htmlspecialchars($name)."'"; 

	$r = dbRow($shelfNameQuery);

	return $r['id'];

}



function getMenuParentById($id)

{

	if($id==0)

	{

		return "No";

	}

	else

	{

		$menuNameQuery = "SELECT menuName FROM  menus WHERE id='".htmlspecialchars($id)."'"; 

		$r = dbRow($menuNameQuery);

		return $r['menuName'];

	}	

}





function multipleCategory($stockId)

{

	

	$r=dbAll("SELECT * FROM category WHERE 1 AND stockId='".$stockId."'");

	$categories = array();

		

	foreach($r as $rs)

	{

		$categories[] = getCategoryById($rs['categoryId']);

	}

	return join(' , ',$categories);

	

	//$vals = json_decode($categoryList);

	//$vals = implode($vals);

	//$multiVal = "";

	//foreach($vals as $val)

	//{

	//	$multiVal .= getCategoryById($val);

	//}

	//return $vals;

}



function getColourOptions($id='')

{

	$r=dbAll("SELECT * FROM colors");

	$options="";

	

	foreach($r as $rs)

	{

		

		$options .="<option ". ($id==$rs['id']?'selected':'') ." value='".$rs['id']."'>".$rs['colorname']."</option>";

	

	}

	return $options;

	

}



function getColorNameById($colorId)

{

	$r=dbRow("SELECT colorname FROM colors WHERE id='".$colorId."'");

	return $r['colorname'];

}



function getBgSizeById($bgId)

{

	$r=dbRow("SELECT size_val FROM bg_sizes WHERE id='".$bgId."'");

	return $r['size_val'];

}







function getSizesWithInput($id,$typeText,$code='',$colours='')

{

	$r=dbAll("SELECT * FROM bg_sizes WHERE 1 AND active");

	$options="";

		

	foreach($r as $rs)

	{

		$Query = "SELECT quantity FROM stockncolor WHERE 1 AND code='".$code."' AND bg_size='".$rs['size_val']."' AND colors='".$colours."'"; 

		$rColours = dbRow($Query);

		$options .= "<div class='form-group' style='border:0;padding:2px;'><label class='control-label col-md-2'>".$rs['size_val']."</label><input type='hidden' name='".$typeText."Size".$id."[]' value='".$rs['size_val']."'>"."<div class='col-md-10'><input type='text' class='form-control' placeholder='Enter Quantity' value='".($rColours[quantity]?$rColours[quantity]:0)."' name='".$typeText."Quantity".$id."[]'></div></div>";

		//$options .=$rs['size_val'];

	

	}

	return $options."<input type='hidden' name='".$typeText."HiddenQty[]' value='".$id."'>";

	

}



function getBigImgById($id)

{

	$Query = "SELECT bigImg FROM productimages WHERE id='".htmlspecialchars($id)."'"; 

	$r = dbRow($Query);

	return $r['bigImg'];

}



function getSmallImgById($id)

{

	$Query = "SELECT smallImg FROM productimages WHERE id='".htmlspecialchars($id)."'"; 

	$r = dbRow($Query);

	return $r['smallImg'];

}



function getBigImgByStockId($stockId)

{

	$Query = "SELECT bigImg FROM productimages WHERE stockId='".htmlspecialchars($stockId)."' GROUP BY stockId"; 

	$r = dbRow($Query);

	return $r['bigImg'];

}



function getSmallImgByStockId($stockId)

{

	$Query = "SELECT smallImg FROM productimages WHERE stockId='".htmlspecialchars($stockId)."' GROUP BY stockId"; 

	$r = dbRow($Query);

	return $r['smallImg'];

}



function getStockIdByImageId($imgId)

{

	$Query = "SELECT * FROM productimages WHERE id=".$imgId." GROUP BY stockId"; 

	$r = dbRow($Query);

	return $r['stockId'];

}



function getCategories($stockId)

{

	$r=dbAll("SELECT * FROM category WHERE 1 AND stockId='".$stockId."'");

	$categories = array();

		

	foreach($r as $rs)

	{

		$categories[$rs['id']] = $rs['categoryId'];

	}

	return $categories;	

}



function getCategoriesById($ctgId) 

{

	$r=@dbAll("SELECT stockId FROM category WHERE 1 AND categoryId='".$ctgId."'");

	$stockId = array();

	if(count($r) > 0)

	{	

		foreach($r as $rs)

		{

			$stockId[] = $rs['stockId'];

		}

		

		return join(",",$stockId);	

	}	

}



function getTags($stockId)

{

	$r=dbAll("SELECT * FROM producttags WHERE 1 AND stockId='".$stockId."'");

	$tags = array();

		

	foreach($r as $rs)

	{

		$tags[$rs['id']] = $rs['tagId'];

	}

	return $tags;

	

}

$parentListnew='';



function getMenuList($parentId=0,$n=1,$selectId=0,$id=0)

{

	

	$rs=dbAll("SELECT * FROM  menus WHERE 1 AND parent=$parentId order by id desc");

//	echo "SELECT * FROM  menus WHERE 1 ".$addParent." AND id !=$id order by id desc";

	if(count($rs)<1)

		return;

		

	foreach($rs as $r)

	{

		$selected = $r[id]==$selectId ?'selected':'';

		

		$menuSpaces='';

		for($j=0;$j<$n;$j++)

			$menuSpaces .='-';

		if($j>0)

		{

			$menuSpaces .='> ';

		}

		if($id != $r[id])

		{

			echo $parentListnew .="<option value='$r[id]' $selected>".$menuSpaces.htmlspecialchars($r['menuName'])."</option>";

		}

		//$parentList .= "<option>".$r['menuName']."</option>";

		getMenuList($r['id'],$n+1,$selectId,$id);

	}

	//echo $parentListnew;

	//return $parentList;	

}



//////////////////////////////////////////////////////////////////////////

function getColorId($clr_name)

{

	$query="select * from colors where colorname = '$clr_name'";

	$data =dbRow($query);

	return $data['id'];

}



function getShelfId($shelf_name)

{

	$data = dbRow("select * from shelf where shelf_name = '$shelf_name'");

	return $data['id'];

}



function getCategoryId($ctg_name)

{

	$data = dbRow("select * from pcategory where cname = '$ctg_name'");

	return $data['id'];

}



function getCategoryByAbbr($ctg_abbr)

{

	$data = dbRow("select id from pcategory where abbrevation = '$ctg_abbr'");

	return $data['id'];

}



function getCustomerId($cust_name)

{

	$data = dbRow("select * from  user_accounts where custname = '$cust_name'");

	return $data['id'];

}



function getCustomerDiscount($id)

{

	$data = dbRow("select discount from  user_accounts where id = '$id'");

	return $data['discount'];

}



function getAbbrv($custid)

{

	$data = dbRow("select * from  user_accounts where id = '$custid'");

	return strtoupper($data['abbrv']);

}



function getSupplier($id)

{

	$data = dbRow("select * from  supplier where id = '$id'");

	return $data['supplier_name'];

}



function getShelfFrmStk($ctg,$code)

{

	$data = dbRow("select * from  stock where category = '$ctg' AND code='$code'");

	return getShelf($data['shelf']);

}

 */


function getWaterMarkImage($id)
{
    $count_data = dbRow("select count(*) as count from productimage where product_id=".$id);
    return $count_data['count'];
}


function getWithoutWatermarkImage($id)
{
	$image_data = dbRow("select * from productimage where product_id=".$id);
	$count = 0;

	if(!empty($image_data))
    {
    	foreach($image_data as $val)
    	{
    		if(file_exists('../../webimage/ww/'.$val['productimage_small']))
    		{
                $count++;
    		}
    	}
    }

    return $count++;
}


//// Functions ////



?>