<?php

/*
Plugin Name: Amazon NSST
Plugin URI: http://manh.xyz
Description: Plugin hỗ trợ amazon riêng cho NSST
Author: Manh Nguyen
Author URI: https://www.facebook.com/manh.heheha
Version: 1.1
*/

// UPdate all price in database


include 'setting.php';

// Update

function nsst_amz_updateAllPrice() {
	global $wpdb;

	$table_name = $wpdb->prefix . "nsstlink"; 
	$data_asin_db = $wpdb->get_results("SELECT * FROM " . $table_name, ARRAY_A);
	foreach($data_asin_db as $value) {
		//var_dump($value);
		//print_r($value['asin']);
		$check = nsst_amz_updateToDatabase($value['asin']);
		//print_r($check);
	}
	
}

// Get price from database, if not have in database, get new and update
function nsst_amz_getPriceFromDB($asin_input) {
	
	global $wpdb;

	$table_name = $wpdb->prefix . "nsstlink"; 
	$data_asin_db = $wpdb->get_row("SELECT * FROM " . $table_name. " WHERE asin = '". $asin_input ."'", ARRAY_A);
	if ($data_asin_db == null) {
		return nsst_amz_getPriceFromAmz($asin_input, 'insert');
	} else {
		//echo 'manh--';
		$now = time();
		$target = strtotime($data_asin_db['time']);
		$diff = $now - $target;

		if ($diff <= 86400 && $data_asin_db['price'] != '0.00') {
        	return $data_asin_db;
		} else {
			return nsst_amz_getPriceFromAmz($asin_input, 'update'); 
		}
	}
}
function nsst_amz_getPriceFromAmz($asin_input, $type) {
	global $wpdb;
	$array_data = array();
	$table_name = $wpdb->prefix . "nsstlink"; 
	// get data from amazon
	$time = date("Y-m-d H:i:s");
	//var_dump($time);
	$response = nsst_amz_getAmazonPrice("com", $asin_input);
	nsst_amz_ghiLog("GET THANH CONG ASIN: " . $asin_input);
	$array_value_asin = array(
		'asin' => $asin_input,
		'time' => $time,
		'price' => $response['price'],
		'amountsaved' =>$response['price_save'],
		'percentagesaved' =>$response['price_percent_save'],
		);

	$array_type_asin = array('%s', '%s', '%s', '%s', '%s');
	if ($type == 'insert') {
		$check = $wpdb->insert($table_name, $array_value_asin, $array_type_asin);
		nsst_amz_ghiLog("INSERT THANH CONG ASIN: " . $asin_input);
 	} else if ($type == 'update') {
 		$check = $wpdb->update($table_name, $array_value_asin, array('asin' => $asin_input), $array_type_asin, array( '%s' ));
		nsst_amz_ghiLog("CAP NHAT DATA THANH CONG ASIN: " . $asin_input);
 	}
	$array_data['time'] = $time;
	$array_data['price'] = $response['price'];
	$array_data['asin'] = $asin_input;
	$array_data['amountsaved'] = $response['price_save'];
	$array_data['percentagesaved'] = $response['price_percent_save'];
	return $array_data;
}
function nsst_amz_updateToDatabase($asin_input) {
	global $wpdb;
	$table_name = $wpdb->prefix . "nsstlink"; 
		$time = date("Y-m-d H:i:s");
		//var_dump($time);
		$response = nsst_amz_getAmazonPrice("com", $asin_input);
		$array_value_asin = array(
			'asin' => $asin_input,
			'time' => $time,
			'price' => $response['price'],
			'amountsaved' =>$response['price_save'],
			'percentagesaved' =>$response['percentagesaved'],
			);

		$array_type_asin = array('%s', '%s', '%s', '%s', '%s' );
		//print_r($asin_input);
		// TODO manh
		$check = $wpdb->update($table_name, $array_value_asin, array('asin' => $asin_input), $array_type_asin, array( '%s' ));
		nsst_amz_ghiLog("CAP NHAT DATA THANH CONG ASIN: " . $asin_input);
		return $check;

}


function nsst_amz_ghiLog($log) {
	/*
	global $wpdb;
	

	$table_name = $wpdb->prefix . "nsstlink_log"; 
	$time = date("Y-m-d H:i:s");
	$array_value_asin = array(
			'id' => '',
			'time' => $time,
			'log' => $log,
			);

		$array_type_asin = array('%s', '%s', '%s');

		$check = $wpdb->insert($table_name, $array_value_asin, $array_type_asin);
		*/

}


//Khởi tạo hàm cho shortcode

// Shotcode price
function nsst_amz_func_my_price($atts, $content) {
	if (!isset($atts['asin'])) $atts['asin'] = "B005QAQFFS";
	if (!isset($atts['text'])) $atts['text'] = "";
	if (!isset($atts['notip'])) $atts['notip'] = "";

	//if (empty($content)) $content = "";
	
	$data = nsst_amz_getPriceFromDB($atts['asin']);
	//var_dump($data);

	$tip = "";
	if ($atts['notip'] == "true") {
		$tip = "*";
	} else {
		$tip = '<span style="cursor: pointer;" title="Prices are accurate as of ' . $data['time'] . '">*</span>';
	}

	if ($atts['text'] == "true") {
		$value = "At the time of publishing, the price was $" . $data['price'] . $tip;
	} else {
		$value = "$" . $data['price'] . $tip;
	}

	if ($data['price'] == '0.00' || $data['price'] == 0) {
		$value = "Currently Not Available" . $tip;
	}
	return $value;
}
// shortcode price save
function nsst_amz_func_my_save_price($atts, $content) {
	if (!isset($atts['asin'])) $atts['asin'] = "B005QAQFFS";
	if (!isset($atts['text'])) $atts['text'] = "";
	if (!isset($atts['notip'])) $atts['notip'] = "";

	//if (empty($content)) $content = "";
	
	$data = nsst_amz_getPriceFromDB($atts['asin']);
	//var_dump($data);

	$tip = "";
	if ($atts['notip'] == "true") {
		$tip = "*";
	} else {
		$tip = '<span style="cursor: pointer;" title="Prices are accurate as of ' . $data['time'] . '">*</span>';
	}

	if ($atts['text'] == "true") {
		$value = "At the time of publishing, the price save $" . $data['amountsaved'] . $tip;
	} else {
		$value = "$" . $data['amountsaved'] . $tip;
	}
	if ($data['amountsaved'] == '0.00' || $data['amountsaved'] == '0' || $data['amountsaved'] == '')
		$value = "No Discount Available At This Time". $tip;
	return $value;
}

// shortcode price save percent
function nsst_amz_func_my_save_percent($atts, $content) {
	if (!isset($atts['asin'])) $atts['asin'] = "B005QAQFFS";
	if (!isset($atts['text'])) $atts['text'] = "";
	if (!isset($atts['notip'])) $atts['notip'] = "";

	//if (empty($content)) $content = "";
	
	$data = nsst_amz_getPriceFromDB($atts['asin']);
	//var_dump($data);

	$tip = "";
	if ($atts['notip'] == "true") {
		$tip = "*";
	} else {
		$tip = '<span style="cursor: pointer;" title="Prices are accurate as of ' . $data['time'] . '">*</span>';
	}

	if ($atts['text'] == "true") {
		$value = "At the time of publishing, the price save " . $data['percentagesaved'] .'%'. $tip;
	} else {
		$value = "" . $data['percentagesaved'] . '%'. $tip;
	}
	
	if ($data['percentagesaved'] == '0.00' || $data['percentagesaved'] == '0' || $data['percentagesaved'] == '')
		$value = "No Discount Available At This Time". $tip;
	return $value;
}



add_shortcode( 'nsstprice', 'nsst_amz_func_my_price' );
add_shortcode( 'nsstsaveprice', 'nsst_amz_func_my_save_price' );
add_shortcode( 'nsstsavepercent', 'nsst_amz_func_my_save_percent' );

// Region code and Product ASIN

// For install database
global $nsst_db_version;
$nsst_db_version = '1.2';

function nsst_amz_nsst_db_install () {
   global $wpdb;

   $table_name = $wpdb->prefix . "nsstlink";
   $table_name_log = $wpdb->prefix . "nsstlink_log"; 

   $charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
  asin VARCHAR(20) NOT NULL,
  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  price VARCHAR(20) NOT NULL,
  amountsaved VARCHAR(20) NOT NULL,
  percentagesaved VARCHAR(20) NOT NULL,
  UNIQUE KEY id (asin)
) $charset_collate;
CREATE TABLE $table_name_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  log VARCHAR(200) NOT NULL,
  PRIMARY KEY (id)
) $charset_collate;";

$sql_log = "";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
//dbDelta( $sql_log );
add_option( 'nsst_db_version', $nsst_db_version );
}

register_activation_hook( __FILE__, 'nsst_amz_nsst_db_install' );
// End database
 
function nsst_amz_getAmazonPrice($region, $asin) {
 
	$xml = nsst_amz_aws_signed_request($region, array(
		"Operation" => "ItemLookup",
		"ItemId" => $asin,
		"IncludeReviewsSummary" => False,
		"ResponseGroup" => "Medium,OfferSummary,Offers",
	));
 	//print_r($xml);
 	//echo 'mmmmmmmanh';
	$item = $xml->Items->Item;
	$title = htmlentities((string) $item->ItemAttributes->Title);
	$url = htmlentities((string) $item->DetailPageURL);
	$image = htmlentities((string) $item->MediumImage->URL);
	/*
	$price = htmlentities((string) $item->Offers->Offer->OfferListing->Price->Amount);
	$price_save = htmlentities((string) $item->Offers->Offer->OfferListing->AmountSaved->Amount);
	$price_percent_save = htmlentities((string) $item->Offers->Offer->OfferListing->PercentageSaved);
	*/
	$price = htmlentities((string) $item->OfferSummary->LowestNewPrice->Amount);
	$priceOffer = htmlentities((string) $item->Offers->Offer->OfferListing->Price->Amount);
	if ($price == null || $price == '0.00') {
		$price = $priceOffer;
	}
	$code = htmlentities((string) $item->Offers->Offer->OfferListing->Price->CurrencyCode);
	
	//print_r($item);
 
	if ($qty !== "0") {
		$response = array(
			"code" => $code,
			"price" => number_format((float) ($price / 100), 2, '.', ''),
			"image" => $image,
			"url" => $url,
			"title" => $title,
			"price_save" =>number_format((float) ($price_save / 100), 2, '.', ''),
			"price_percent_save" =>$price_percent_save,
		);
	}
 
	return $response;
}
 
function nsst_amz_getPage($url) {
 
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$html = curl_exec($curl);
	curl_close($curl);
	return $html;
}
 
function nsst_amz_aws_signed_request($region, $params) {
 
	
	$my_nsst_settings_page = get_option('my_nsst_option_name');
	//var_dump($my_nsst_settings_page);
	//$my_nsst_settings_page = new MyNSSTSettingsPage();
 	$public_key = $my_nsst_settings_page['nsst_public_key'];
 	$private_key = $my_nsst_settings_page['nsst_private_key'];

 	//var_dump($public_key);
 	//var_dump($private_key);
	$method = "GET";
	$host = "ecs.amazonaws." . $region;
	$host = "webservices.amazon." . $region;
	$uri = "/onca/xml";
 
	$params["Service"] = "AWSECommerceService";
	$params["AssociateTag"] = "affiliate-20"; // Put your Affiliate Code here
	$params["AWSAccessKeyId"] = $public_key;
	$params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
	$params["Version"] = "2011-08-01";
 
	ksort($params);
 
	$canonicalized_query = array();
	foreach ($params as $param => $value) {
		$param = str_replace("%7E", "~", rawurlencode($param));
		$value = str_replace("%7E", "~", rawurlencode($value));
		$canonicalized_query[] = $param . "=" . $value;
	}
 
	$canonicalized_query = implode("&", $canonicalized_query);
 
	$string_to_sign = $method . "\n" . $host . "\n" . $uri . "\n" . $canonicalized_query;
	$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
	$signature = str_replace("%7E", "~", rawurlencode($signature));
 
	$request = "http://" . $host . $uri . "?" . $canonicalized_query . "&Signature=" . $signature;
	$response = nsst_amz_getPage($request);
 
	//var_dump($response);
	//print_r($response);
 
	$pxml = @simplexml_load_string($response);
	if ($pxml === False) {
		return False;// no xml
	} else {
		return $pxml;
	}
}

?>