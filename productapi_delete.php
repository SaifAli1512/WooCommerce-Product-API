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
	    echo "Begin";
// 		echo "Uncomment the die() here. I just saved you from a huge headache.";
// 		die;
		$d = getProductsforDelete();
		delete_the_products($d);
		status_message("Successfully Updated!");
	}
	catch(HttpClientException $e){
	    echo $e->getMessage();
	}
	
	function delete_the_products($data) {
		global $woocommerce, $conn, $config, $prefix;
		$limit = $data[0]['import_limit'];
		$offset = $data[0]['import_offset'];
		$q = $conn->query("
 	        SELECT 
 	            DISTINCT(wpp.ID)
 			FROM 
 			    `".$prefix."posts` AS wpp 
 			LEFT JOIN 
 			    `".$prefix."postmeta` AS wppm 
 			ON wppm.post_id = wpp.ID
 			WHERE 
 			    ( post_date BETWEEN '2019-10-11 00:00:00' AND '2019-10-15 10:00:00') AND 
 			    wpp.post_type = 'product'
 			ORDER BY wpp.ID DESC
 			LIMIT $limit
		");
// 		$q = $conn->query("
//  	        SELECT 
//  	            DISTINCT(wpp.ID)
//  			FROM 
//  			    `".$prefix."posts` AS wpp 
//  			LEFT JOIN 
//  			    `".$prefix."postmeta` AS wppm 
//  			ON wppm.post_id = wpp.ID
//  			WHERE 
//  			    wpp.ID NOT IN(
//      				SELECT ID
//      			    	FROM 
//      			    	`".$prefix."posts` AS wpp2 
//      				LEFT JOIN 
//      				    `".$prefix."postmeta` AS wppm2
//  					ON  wppm2.post_id = wpp2.ID
//      				WHERE 
//      				    wppm2.meta_key = '_thumbnail_id'
//  			    ) AND 
//  			    wpp.post_type = 'product'
//  			ORDER BY wpp.ID DESC
//  			LIMIT $limit OFFSET $offset
// 		");
        
		if($q->num_rows){
			$q = $q->fetch_all();
    		foreach($q as $id):
    			$woocommerce->delete('products/'.$id[0], ['force'=>true]);
    		endforeach;
		}
	}

	function status_message( $message ) {
		echo $message . "\r\n";
	}
	
?>
