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
		
		"productprice_multiplier" =>$config['api_price_multiplier']
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
		$json = getModifiedproducts();
		$data = separate_products_and_variants($json);
		$color_options =get_attribute_options($json, 'colour');
		$size_options = get_attribute_options($json, 'size');
		$plating_options = get_attribute_options($json, 'plating');
		import_products_to_woo($data, $color_options, $size_options, $plating_options);
		
		status_message("Products Successfully Imported and Updated");
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
	
	function make_attributes(){
		global $conn, $prefix;
		$q = "SELECT attribute_name
				FROM `".$prefix."woocommerce_attribute_taxonomies` as wpat 
				WHERE attribute_name = 'color' 
				OR attribute_name = 'size' 
				OR attribute_name = 'plating'
				ORDER BY attribute_name"; 
		$r = $conn->query($q);
		$i = 0;
		if($r->num_rows == 0){
			add_attribute('color');
			add_attribute('size');
			add_attribute('plating');
		}
		elseif($r->num_rows < 3){
			$r = $r->fetch_assoc();
			if($r[$i++]['attribute_name'] != 'color'){
				add_attribute('color');
				$i = 0;
			}
			if($r[$i++]['attribute_name'] != 'plating'){
				add_attribute('plating');
				$i = 0;
			}
			if($r[$i++]['attribute_name'] != 'size'){
				add_attribute('size');
				$i = 0;
			}
		}
	}

	function add_attribute($attr){
		global $woocommerce;
		$data = ['name'=>ucfirst($attr)];
		$woocommerce->post('products/attributes', $data);
	}
	
	function get_product_category_ids($categories) {
		global $woocommerce, $conn, $prefix;
		foreach ($categories as $category) {
		    $category = trim($category);
			$q = "SELECT wpt.term_id as term_id 
					FROM `".$prefix."terms` as wpt 
					JOIN `".$prefix."term_taxonomy` as wptt 
						on wptt.term_id = wpt.term_id 
					where wpt.name LIKE '".$category."' AND wptt.taxonomy= 'product_cat'";
			$r = $conn->query($q);
			if($r->num_rows){
				$r = $r->fetch_assoc();
				$ids[]['id'] = $r['term_id'];
			}
			else{
				if(!empty($category)):
					$new = ['name'=>$category];
					$new_id = $woocommerce->post('products/categories', $new);
					$ids[]['id'] = $new_id->id;
				endif;
			}
		}
		return $ids;
	}
	
	function get_product_tag_ids($tags){
		global $woocommerce, $conn, $prefix;
		foreach ($tags as $tag) {
		    $tag = trim($tag);
			$q = "SELECT wpt.term_id as term_id 
					FROM `".$prefix."terms` as wpt 
					JOIN `".$prefix."term_taxonomy` as wptt 
						on wptt.term_id = wpt.term_id 
					where wpt.name = '".ltrim($tag)."' AND wptt.taxonomy= 'product_tag'";
			$r = $conn->query($q);
			if($r->num_rows){
				$r = $r->fetch_assoc();
				$ids[]['id'] = $r['term_id'];
			}
			else{
				if(!empty($tag)):
					$new = ['name'=>$tag];
					$new_id = $woocommerce->post('products/tags', $new);
					$ids[]['id'] = $new_id->id;
				endif;
			}
		}
		return $ids;
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
	
	function featured_image_present($images){
	    global $config;
	    foreach($images as $product_image)
			if(!empty($product_image)):
				$file = $config['download_image_path'].$product_image;
				$file_exists = file_exists($file);
				if($file_exists)
				    return true;
			endif;
		return false;
	}

	function import_products_to_woo($json, $color_options, $size_options, $plating_options) {
		global $woocommerce, $conn, $config, $prefix;
		$array_pointer = &$json;
		$product = array();
		$woo_image_ids = array();
		foreach($json['products'] as $key=>$pd):
		    echo "\n";
		    if(featured_image_present($array_pointer['all_images'][$key])):
    			$duplicate = check_product_duplicacy($pd['product_sku']);
    			$color_id = $conn->query("SELECT attribute_id from `".$prefix."woocommerce_attribute_taxonomies` where attribute_name = 'color'");
    			if($color_id->num_rows){
    				$color_id = $color_id->fetch_row();
    				$color_id = $color_id[0];
    			}
    			$size_id = $conn->query("SELECT attribute_id from `".$prefix."woocommerce_attribute_taxonomies` where attribute_name = 'size'");
    			if($size_id->num_rows){
    				$size_id = $size_id->fetch_row();
    				$size_id = $size_id[0];
    			}
    			$plating_id = $conn->query("SELECT attribute_id from `".$prefix."woocommerce_attribute_taxonomies` where attribute_name = 'plating'");
    			if($plating_id->num_rows){
    				$plating_id = $plating_id->fetch_row();
    				$plating_id = $plating_id[0];
    			}
    			$old_duplicate = $duplicate;
    			$temp_attr = array();
    			if( !$duplicate ){
    				$product[$key]['type'] = 'variable';
    				
    				$categories = explode(",",$pd['category']);
    				$categories = get_product_category_ids($categories);
    				$product[$key]['categories'] = $categories;
    
    				$tags = explode(",",$pd['tags_title']);
    				$tags = get_product_tag_ids($tags);
    				$product[$key]['tags'] = $tags;
    
    				$product[$key]['sku'] = (string) $pd['product_sku'];
    				$product[$key]['name'] = (string) ucfirst($pd['product_name']);
    				$product[$key]['slug'] = (string)str_replace(' ','-', strtolower($pd['product_alias']));
    
    				$product[$key]['description'] = '[html_block id="4237"]';
    				$product[$key]['short_description'] = isset($pd['short_desc'])? str_replace("manekratna","oyekudiye",$pd['short_desc']):"No short description.";
    				$pd['product_code'] = empty($pd['product_code'])? 0:$pd['product_code'];
    				$product[$key]['regular_price'] = (string) ((int)$pd['product_code'] * $config['productprice_multiplier']);
    				$product[$key]['manage_stock'] = (bool) false;
    				
    				$product[$key]['weight'] = isset($pd['product_weight'])? (string) $pd['product_weight']:"0.00";
    				if((!empty($color_options[$key]['colour']))){
    					$temp_attr[] = 
    							[
    								'id' => $color_id,
    								'variation' => true,
    								'options' => explode(',', $color_options[$key]['colour'])
    							];
    				}
    				if((!empty($size_options[$key]['size']))){
    					$temp_attr[] = 
    							[
    					            'id' => $size_id,
    					            'variation' => true,
    					            'options' => explode(',', $size_options[$key]['size'])
    					        ];
    				}
    				if((!empty($plating_options[$key]['plating']))){
    					$temp_attr[] = 
    							[
    					            'id' => $plating_id,
    					            'options' => explode(',',$plating_options[$key]['plating'])
    					        ];
    				}
    				$product[$key]['attributes'] = $temp_attr;
    				if(!empty($array_pointer['variants'][$key])):
        				$default_color = $array_pointer['variants'][$key][0]['colour_name'];
        				if(!empty($default_color))
            				$product[$key]['default_attributes'][0] = 
            			                [
            			                    'id' => '2',
            			                    'option' => $default_color
            			                ];
            		endif;
    				$wc_product = $woocommerce->post('products', $product[$key]);
    				$duplicate = $wc_product->id;
    				if($wc_product)
    					status_message('New Product Added --> ID: '. $wc_product->id."<br>");
					import_metadata($pd, $wc_product->id);
    				unset($product);
    			}
    			else{
    				unset($product);
    				$old_data = $woocommerce->get('products/'.$duplicate);
            	    $old_data = $old_data->attributes;
            		$old_color_options = implode(',' ,$old_data[0]->options);
            		if($old_data[1]->name == 'Size')
            		    $old_size_options = implode(',' ,$old_data[1]->options);
            		    
    				if((!empty($color_options[$key]['colour']))){
    				    $color_options[$key]['colour'] .= ','.$old_color_options;
    					$temp_attr[] = 
    							[
    								'id' => $color_id,
    								'variation' => true,
    								'options' => explode(',', $color_options[$key]['colour'])
    							];
    				}
    				if((!empty($size_options[$key]['size']))){
    				    $size_options[$key]['size'] .= ','.$old_size_options;
    					$temp_attr[] = 
    							[
    					            'id' => $size_id,
    					            'variation' => true,
    					            'options' => explode(',', $size_options[$key]['size'])
    					        ];
    				}
    				$product[$key]['attributes'] = $temp_attr;
    				$product[$key]['stock_quantity'] = $pd['product_qty'];
    				$product[$key]['manage_stock'] = (bool) false;
    				$woocommerce->put('products/'.$duplicate, $product[$key]);
    				echo "Existing Product  - ".$duplicate."   ...Updated\n";
    				$wc_product = $duplicate;
    			}
    			
    			if(!empty($array_pointer['variants'][$key])):
    				$i = 0;
    				$color_terms = explode(',',$color_options[$key]['colour']);
    				$size_terms = explode(',',$size_options[$key]['size']);
    				foreach($array_pointer['variants'][$key] as $variant):
    
    					$duplicate_variant = check_variant_duplicacy($duplicate, (!empty($color_terms[$i]))? $color_terms[$i]: '0', (!empty($size_terms[$i]))? $size_terms[$i]:'0');
    					if(!$duplicate_variant){
    						$temp = [	
    								'manage_stock'=>(bool) true,
    								'regular_price'=>(string) ((int)$variant['product_code'] * $config['productprice_multiplier']),
    								'stock_quantity'=>$variant['product_qty']
    							];
    						
    						unset($temp_attr);
    						if(!empty($color_terms[$i]))
    						{
    							$temp_attr = 
    									[
    										'id' => $color_id, 
    										'option' => $color_terms[$i]
    									];
    							$temp['attributes'][0] = $temp_attr;
    						}
    						unset($temp_attr);
    						if(!empty($size_terms[$i])){
    							$temp_attr = 
    									[
    							            'id' => $size_id,
    							            'option' => $size_terms[$i]
    							        ];
    							$temp['attributes'][1] = $temp_attr;
    						}
							$images = explode('|', $variant['big_images']);
							$once = 1;
							foreach($images as $product_image):
								if(!empty($product_image)):
									$file = $config['download_image_path'].$product_image;
									$file_exists = file_exists($file);
									if($file_exists):
										$file = base64_encode(file_get_contents($file));
										$targetUrl = $config['upload_image_path']."receive_images.php";
										$post = array('file_name' => $product_image, 'data'=>$file, 'request_type'=>'create');
										$ch = curl_init();
										curl_setopt ($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
								        curl_setopt ($ch, CURLOPT_MAXREDIRS, 100);
								        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, false);
								        curl_setopt ($ch, CURLOPT_VERBOSE, 0);
								        curl_setopt ($ch, CURLOPT_HEADER, 1);
								        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 1000);
								        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
								        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
								        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt ($ch, CURLOPT_URL,$targetUrl);
										curl_setopt ($ch, CURLOPT_POST,1);
										curl_setopt ($ch, CURLOPT_TIMEOUT, 4000);
										curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
										$result = curl_exec($ch);
										curl_close ($ch);
										if($once):
											$temp['image'] = array (
									                    'src' => $config['upload_image_path'].$product_image,
									                    'name' => $pd['product_name'],
									                    'alt' => $pd['product_name']
													);
											$once = 0;
										else:
										    $extra_variant_images[] = $product_image;
										endif;
									endif;
								endif;
							endforeach;
    						$vid = $woocommerce->post('products/'.$duplicate.'/variations', $temp);
    						$woo_image_ids[] = $vid->image->id;
    						if($vid)
    							status_message('   New Variant added --> ID: '. $vid->id."<br>");
    					}
    					else{
    					    unset($temp);
    					    $temp = [	
    					            'manage_stock'=>(bool) true,
    								'stock_quantity'=>$variant['product_qty'],
    							];
    						$woocommerce->post('products/'.$duplicate.'/variations/'.$duplicate_variant, $temp);
    						echo "     Existing Variant  - ".$duplicate_variant."   ...Updated\n";
    					}
    					unset($temp);
    					$i++; 
    				endforeach;
    			endif;
    			if(!empty($woo_image_ids)):
    			    $pimage['images'] = array();
    				$m = ($old_duplicate)? 1:0;
    				if($old_duplicate):
    				    $ids = get_old_image_ids($duplicate);
    				    if($ids != 0):
        				    $ids = explode(',',$ids);
        				    foreach($ids as $id)
        				        $woo_image_ids[] = $id;
        				endif;
    				endif;
        	        foreach($woo_image_ids as $id):	
        				$pimage['images'][$m] = array
        					(
        					    'id'=>$id,
        	                    'position'=> ($m==0)? '0':''
        					);
        				$m++;
        		    endforeach;
        		    unset($woo_image_ids);
    			    $woocommerce->put('products/'.$duplicate, $pimage);
    			endif;
    		endif;
		endforeach;
		$targetUrl = $config['upload_image_path']."receive_images.php";
		$post = array('request_type'=>'delete');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$targetUrl);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$result = curl_exec($ch);	
		curl_close ($ch);
	}	
	
	function get_old_image_ids($pid){
	    global $conn, $prefix;
	    $q = $conn->query("
			SELECT meta_value
			FROM `".$prefix."postmeta` 
			WHERE post_id = $pid AND meta_key = '_product_image_gallery'");
		if($q->num_rows){
			$q = $q->fetch_assoc();
			return $q['meta_value'];
		}
		return 0;
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
	
	function import_metadata($pdata, $pid){
		global $conn, $prefix;
		$search = ['manek','ratna'];
		$replace = ['Oye', 'Kudiye'];
		$query = "INSERT INTO `".$prefix."postmeta` VALUES ('','".$pid."','_yoast_wpseo_title', '".str_ireplace($search, $replace,$pdata['product_metatitle'])."')";
		$conn->query($query);
		$query = "INSERT INTO `".$prefix."postmeta` VALUES ('','".$pid."','_yoast_wpseo_metadesc', '".str_ireplace($search, $replace, $pdata['product_metadescription'])."')";
		$conn->query($query);
	}
	
	function parse_json( $file ) {
		$json = json_decode(file_get_contents($file), true);
		if(is_array($json) && !empty($json))
			return $json;	
		else
			die( 'An error occurred while parsing ' . $file . ' file.' );
	}
	
	function status_message( $message ) {
		echo $message . "\r\n";
	}
	
?>
