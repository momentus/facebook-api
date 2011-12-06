<?php

/* need to add nice facebook api exception handling */

class Facebook {
	public $application_id = "";
	public $application_secret = "";
	public $code = "";
	public $access_token = "";
	public $signed_request = "";
	public $facebook_canvas_url = "";
	public $redirect_uri;
	
	/* two different facebook domains are used in authenticating */
	public $graph_url = "https://graph.facebook.com";
	public $auth_base_url = "https://www.facebook.com";

	function __construct($config) {
		//setup facebook obj
		$this->application_id = $config["app_id"];
		$this->application_secret = $config["app_secret"];
		if($config["fb_canvas_url"]){
			$this->facebook_canvas_url = $config["fb_canvas_url"];
		}
	}
	
	public function getSignedRequest(){
		/* 
		   If hosted app, get the parsed request
		   Return entire request - because you may need returns from application requests, or other app_data values
		   Also, assign to local properties access token and user id if available.
		*/
		
		
		$signed_request = $_REQUEST['signed_request'];
		$this->signed_request = $this->parse_signed_request($signed_request);

		/* assign frequently used public properties */
		$this->uid = $this->signed_request["user_id"];
		if(isset($this->signed_request["oauth_token"])){
			/* will only be present if user has already auth'd app and the token is valid */
			$this->access_token = $this->signed_request["oauth_token"];
		}
		return $this->signed_request;
		
	}
	
	public function getAuthUri($redirect_uri, $perms){
		/* 
			create the url that initiates the authentication process 
			
		*/
		if(!$redirect_uri){
			$redirect_uri = $this->facebook_canvas_url;
		} 
		$this->redirect_uri = $redirect_uri;
		$auth_uri = $this->auth_base_url . "/dialog/oauth?client_id=".$this->application_id."&scope=".$perms."&".$this->application_secret."&redirect_uri=".urlencode($redirect_uri);
		return $auth_uri;
	
	}
	
	public function getAccessToken($params){
		/* get the access token again from Facebook, with a valid code (passed in or in request) 
		*/
	
		/* may require one or both or neither params "code" and "redirect_uri"
			 Note: redirect_uri must be the same one used in the creation of the initial auth link.
			 The redirect_uri must also be the same domain as in the facebook app definition
		*/
		if(isset($this->access_token) && strlen($this->access_token) > 15){
			return $this->access_token;
		}
		if($_REQUEST['code'] && ($params["code"] == "")){
			$code = $_REQUEST['code'];
		}
		if($params["code"]){
			$code = $params["code"];
		}
		if(isset($params["redirect_uri"])){
			$this->redirect_uri = $params["redirect_uri"];
		}
	
		$graph_uri = $this->graph_url . "/oauth/access_token?code=".$code."&redirect_uri=".urlencode($this->redirect_uri);
		$graph_results = file_get_contents($graph_uri);
		parse_str($graph_results);
		if(isset($access_token)){
			$this->access_token = $access_token;
			return $this->access_token;
		}
	}
	
	function parse_signed_request($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2); 
	
		// decode the data
		$sig = $this->base64_url_decode($encoded_sig);
		$data = json_decode($this->base64_url_decode($payload), true);
	
		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			/* btw throwing lots of errors */
//			error_log('Unknown algorithm. Expected HMAC-SHA256');
			return null;
		}
	
		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $this->application_secret, $raw = true);
		if ($sig !== $expected_sig) {
			error_log('Bad Signed JSON signature!');
			return null;
		}
		return $data;
	}
	
	function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}

	function checkRequest(){
	  /* return the user's id if the signed request is present, similar to 
	     the PHP-SDK's "getUser()" method
	  
	  */
	  
		$this->getSignedRequest();
		if( (strlen($this->access_token) < 15) && isset($_REQUEST["code"]) ){
			/* application access tokens are largely useless, if not in signed_request
			   get from code in request/get */
			   
			$this->getAccessToken();
		}
		return $this->uid;
		
	}
	
	public function api($object, $params){
		if(!$object || $object == ""){
			$object = "me";
		}
		if(isset($params)){
			foreach($params as $key=>$value){
				$paramstr .= $key . "=".$value."&";
			}
		} else {		
			$paramstr = "";
		}
		if($this->access_token){
			$graph_request = $this->graph_url."/".$object."?access_token=".$this->access_token."&".$paramstr;
			$graph_results = file_get_contents($graph_request);
			$graph_results = json_decode($graph_results);
			return $graph_results;
		}

	}

}


?>
