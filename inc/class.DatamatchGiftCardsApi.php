<?php


class DatamatchGiftCardsApi extends DatamatchGiftCards {
	
	static $cache = array();
	static $apiKey = '';
	
	const DATAMATCH_SERVER_URL = 'https://rest.datamatch.nl';
	
	const API_ERROR_BAD_REQUEST = '"400 Bad Request"';
	const API_ERROR_UNKNOWN_CARD = '"Unknown card"';
	const API_ERROR_INVALID_KEY = '"Your API key or refresh token is invalid or has expired."';
	
	public static function checkGiftCardId($giftCardId) {
		if (!isset(self::$cache[$giftCardId])) {
			$result = self::requestDatamatchServer('/giftcard/' . $giftCardId);
			if (!in_array($result, array(self::API_ERROR_BAD_REQUEST, self::API_ERROR_UNKNOWN_CARD, self::API_ERROR_INVALID_KEY))) {
				self::$cache[$giftCardId] = json_decode($result);
				/* Example of json_decode result: 
					stdClass Object
					(
						[giftcardId] => 52384382e69511e5856b22000a24c03c
						[giftcardValue] => 65
						[memberId] => 79270012c68b11e59f1722000a24c03c
						[memberName] => Moritz
					)
				 */
			}
			else {
				self::$cache[$giftCardId] = false;
				if ($result == self::API_ERROR_INVALID_KEY) {
					wc_add_notice( __( 'API Key used to check Gift Card is invalid. Please contact site owner to resolve this issue.', 'dtm_gift_cards' ), 'error' );
				}
			}
		}
		return self::$cache[$giftCardId] ? true : false;
	}
	
	public static function getGiftCardBalance($giftCardId) {
		if (!isset(self::$cache[$giftCardId])) {
			self::checkGiftCardId($giftCardId);
		}
		if (is_object(self::$cache[$giftCardId])) {
			return self::$cache[$giftCardId]->giftcardValue;
		}
		else return false;
	}
	
	public static function getGiftCardLongId($giftCardId) {
		if (!isset(self::$cache[$giftCardId])) {
			self::checkGiftCardId($giftCardId);
		}
		if (is_object(self::$cache[$giftCardId])) {
			return self::$cache[$giftCardId]->giftcardId;
		}
		else return false;
	}
	
	public static function prepareGiftCardValue($value) {
		return round($value,2);
	}
	
	public static function updateGiftCardBalance($giftCardId, $newValue) {
		$postData = array(
			'giftcardId'	=> $giftCardId,
			'giftcardValue'	=> self::prepareGiftCardValue($newValue)
		);
		$result = self::postDatamatchServer('/giftcard/' . $giftCardId, $postData);
		
		if (!in_array($result, array(self::API_ERROR_BAD_REQUEST, self::API_ERROR_UNKNOWN_CARD, self::API_ERROR_INVALID_KEY))) {
			self::$cache[$giftCardId] = json_decode($result);
			return self::$cache[$giftCardId]->giftcardValue;
		}
		else {
			self::$cache[$giftCardId] = false;
			return false;
		}
	}
	
	public static function getApiKey() {
		if (!self::$apiKey) {
			$settings = self::getSettings();
			self::$apiKey = $settings['api_key'];
		}
		return self::$apiKey;
	}
	
	/**
	 * Send GET request to Datamatch server using API key to autenticate
	 * @param string $path path to request, with leading slash
	 */
	public static function requestDatamatchServer($path) {
		$url = self::DATAMATCH_SERVER_URL . $path;
		$get = array();
		$apiKey = self::getApiKey();
		$options = array(CURLOPT_HTTPHEADER => array('Authorization: ' . $apiKey));
		return self::get($url, $get, $options);
	}
	
	/**
	 * Send POST request to Datamatch server using API key to autenticate 
	 * @param string $path path to request, with leading slash
	 * @param array $postData data to send with request
	 */
	public static function postDatamatchServer($path, $postData) {
		$url = self::DATAMATCH_SERVER_URL . $path;
		$apiKey = self::getApiKey();
		$options = array(CURLOPT_HTTPHEADER => array('Authorization: ' . $apiKey));
		return self::post($url, $postData, $options);
	}
	
	/** 
	 * Send a POST requst using cURL 
	 * @param string $url to request 
	 * @param array $post values to send 
	 * @param array $options for cURL 
	 * @return string 
	 */ 
	public static function post($url, array $post = NULL, array $options = array()) {
	   if (!function_exists('curl_init')) {
		   self::log("DatamatchGiftCardsApi::post() --- curl_init not found");
		   return false;
	   }

	   $defaults = array( 
		   CURLOPT_POST => 1, 
		   CURLOPT_HEADER => 0, 
		   CURLOPT_URL => $url, 
		   CURLOPT_FRESH_CONNECT => 1, 
		   CURLOPT_RETURNTRANSFER => 1, 
		   CURLOPT_FORBID_REUSE => 1, 
		   CURLOPT_TIMEOUT => 8, 
		   CURLOPT_POSTFIELDS => http_build_query($post) 
	   ); 

	   $ch = curl_init(); 
	   
	   $opts = ($options + $defaults);
	   self::log('DatamatchGiftCardsApi::post() : url = ' . $url . ' ,  opts ');
	   self::log($opts);
	   
	   curl_setopt_array($ch, $opts);

	   if( ! $result = curl_exec($ch)) { 
		   $curlError = curl_error($ch);
		   self::log('DatamatchGiftCardsApi::post() : Failed to get data from remote server.' . $curlError);
	   }

	   curl_close($ch); 
	   
	   self::log('DatamatchGiftCardsApi::post() : result');
	   self::log($result);
	   return $result; 
	} 

	/** 
	 * Send a GET requst using cURL 
	 * @param string $url to request 
	 * @param array $get values to send 
	 * @param array $options for cURL 
	 * @return string 
	 */ 
	public static function get($url, array $get = NULL, array $options = array()) {
	   if (!function_exists('curl_init')) {
		   self::log("DatamatchGiftCardsApi::get() --- curl_init not found");
		   return false;
	   }

	   $defaults = array( 
		   CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
		   CURLOPT_HEADER => 0, 
		   CURLOPT_RETURNTRANSFER => TRUE, 
		   CURLOPT_TIMEOUT => 8 
	   ); 

	   $ch = curl_init(); 
	   $opts = ($options + $defaults);
	   self::log('DatamatchGiftCardsApi::get() : url = ' . $url . ' ,  opts ');
	   self::log($opts);
	   
	   curl_setopt_array($ch, $opts);
	   
	   if( ! $result = curl_exec($ch)) { 
		   $curlError = curl_error($ch);
		   self::log('DatamatchGiftCardsApi::get() : Failed to get data from remote server.' . $curlError);
	   }
	   curl_close($ch); 
	   
	   self::log('DatamatchGiftCardsApi::get() : result');
	   self::log($result);
	   return $result; 
	} 
}
