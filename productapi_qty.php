<?php  
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
    ini_set('max_execution_time', 30000);
	$baseDirSplit = explode("/",dirname($_SERVER['REQUEST_URI']));
	if(!isset($baseDirSplit) || empty($baseDirSplit[0]))
		$baseDir="/manekratna/";
	else
		$baseDir= "/manekratna/".$baseDirSplit[0]."/";
    echo "<pre>";
	require '../functions.php';
	require '../ww.incs/basics_temp.php';
	require 'vendor/autoload.php';
	use Automattic\WooCommerce\Client;
	use Automattic\WooCommerce\HttpClient\HttpClientException;
	
    $api_url_key =  substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'],'?'));
    
	$config = array();
	$config = dbRow("SELECT * FROM productapi WHERE api_status = 1 AND api_deleted = 0 AND api_url_key = '$api_url_key' ORDER BY api_id ASC");
	
	if(empty($config)){
		echo '<b>No API found with URL Key- '.$api_url_key.'.';
		die;
	}
	$config = [
		/*Receiving Database INFO*/
		"db_ip" =>$config['api_db_ip'],
		"db_user" =>$config['api_db_user'],
		"db_pass" =>$config['api_db_pass'],
		"db_name" =>$config['api_db_name'],
		"db_prefix" =>$config['api_db_prefix'],
		/*Receiving Database INFO*/
		
		/*WooCommerce Info*/
		"woo_domain" =>$config['api_woo_domain'],
		"woo_consumer_key" =>$config['api_woo_ckey'],
		"woo_secret_key" =>$config['api_woo_skey'],
		/*WooCommerce Info*/

		/*Image URL Info*/
		"download_image_path" =>$config['api_download_image_path'],
		"upload_image_path" =>$config['api_upload_image_path'],
		/*Image URL Info*/
		
		"productprice_multiplier" =>$config['api_price_multiplier'],
	];
	$conn = new mysqli(
			$config['db_ip'],
			$config['db_user'],
			$config['db_pass'],
			$config['db_name']
		);
	$woocommerce = new Client(
	    $config['woo_domain'],
	    $config['woo_consumer_key'],
	    $config['woo_secret_key'],
	    [
	        'wp_api' => true,	
	        'version' => 'wc/v3',
    		'query_string_auth' => true,
		    'verify_ssl' => false
	    ]
	);
	$prefix = $config['db_prefix'];
	try{
		$json = getModifiedproductsQty();
		$data = separate_products_and_variants($json);
		$color_options =get_attribute_options($json, 'colour');
		$size_options = get_attribute_options($json, 'size');
		$plating_options = get_attribute_options($json, 'plating');
		import_products_to_woo($data, $color_options, $size_options, $plating_options);
		status_message("\nQuantities Successfully Updated.");
	}
	catch(HttpClientException $e){
	    echo $e->getMessage();
	}
	
	function get_attribute_options($json, $attr_type){
		$pid = 0 ;
		foreach ($json as $value)
			if($pid != $value['product_id']){
				if(!empty($value[$attr_type."_name"])){
					$pid = $value['product_id'];
					$string[$value['product_id']][$attr_type] = $value[$attr_type."_name"];
				}
			}
			else {
				if($attr_type == 'plating')
					continue;
				if(!empty($value[$attr_type."_name"]))
					$string[$value['product_id']][$attr_type] .= ",".$value[$attr_type."_name"];
			}
		return $string;
	}
	
	function check_product_duplicacy($sku){
		global $conn, $prefix;
		$q = $conn->query("
			SELECT ID
			FROM `".$prefix."posts` AS wpp 
			LEFT JOIN `".$prefix."postmeta` AS wppm 
				ON wppm.post_id = wpp.ID
			WHERE wpp.ID NOT IN(
				SELECT ID
				FROM `".$prefix."posts` AS wpp2 
				LEFT JOIN `".$prefix."postmeta` AS wppm2
					ON wppm2.post_id = wpp2.ID
				WHERE wppm2.meta_key = '_wp_trash_meta_time'
			) AND wppm.meta_key = '_sku' AND wppm.meta_value = '".$sku."' 
			ORDER BY ID DESC 
			LIMIT 1
		");
		if($q->num_rows){
			$q = $q->fetch_assoc();
			return $q['ID'];
		}
		return 0;
	}

	function check_variant_duplicacy($pid, $color, $size){
		global $conn, $prefix;
		if( $color != '0' && $size != '0' )
			$excerpt = "Color: ".$color.", Size: ".$size;
		elseif( $size == '0' && $color != '0' )
			$excerpt = "Color: ".$color;
		elseif( $color == '0' && $size != '0' )
			$excerpt = "Size: ".$size;
		$q = $conn->query("
			SELECT ID
			FROM `".$prefix."posts` AS wpp 
			WHERE post_excerpt = '".$excerpt."' AND post_parent = '".$pid."'");
		if($q->num_rows){
			$q = $q->fetch_assoc();
			return $q['ID'];
		}
		return 0;
	}

	function import_products_to_woo($json, $color_options, $size_options, $plating_options) {
		global $woocommerce, $conn, $config, $prefix;
		$array_pointer = &$json;
		$product = array();
		foreach($json['products'] as $key=>$pd):
			$duplicate = check_product_duplicacy($pd['product_sku']);
			$old_duplicate = $duplicate;
			if( !$duplicate ){
			 /*DO NOTHING*/   
			}
			else{
				unset($product);
				$product[$key]['stock_quantity'] = $pd['product_qty'];
				$product[$key]['manage_stock'] = (bool) false;
				$woocommerce->put('products/'.$duplicate, $product[$key]);
				echo "Duplicate Product  - ".$duplicate."   ...Updated\n";
			}
			if(!empty($array_pointer['variants'][$key])):
				$i = 0;
				$color_terms = explode(',',$color_options[$key]['colour']);
				$size_terms = explode(',',$size_options[$key]['size']);
				foreach($array_pointer['variants'][$key] as $variant):
					$duplicate_variant = check_variant_duplicacy($duplicate, (!empty($color_terms[$i]))? $color_terms[$i]: '0', (!empty($size_terms[$i]))? $size_terms[$i]:'0');
					if(!$duplicate_variant){
					    /*DO NOTHING*/   
					}
					else{
					    unset($temp);
					    $temp = [	
					            'manage_stock'=>(bool) true,
								'stock_quantity'=>$variant['product_qty'],
							];
						$woocommerce->post('products/'.$duplicate.'/variations/'.$duplicate_variant, $temp);
						echo "     Duplicate Variant  - ".$duplicate_variant."   ...Updated\n";
					}
					unset($temp);
					$i++; 
				endforeach;
			endif;
		endforeach;
		
	}	

	function separate_products_and_variants( $json ){
		$products = array();
		$variants = array();
		$all_images = array();
		$pid = 0;
		foreach($json as $product):
			if($pid != $product['product_id']){
				$pid = $product['product_id'];
				$products[$pid] = $product;
				unset($products[$pid]['product_id'],$products[$pid]['colour_name'],$products[$pid]['size_name']);
			}
			if(!empty($product['colour_name'] || $product['size_name'])){
				$variants[$pid][] = $product;
				$all_images[$pid][] = $product['big_images'];
			}
		endforeach;
		return ['variants'=>$variants,'products'=> $products, 'all_images'=>$all_images];
	}
	
	function status_message( $message ) {
		echo $message . "\r\n";
	}
	
?>
