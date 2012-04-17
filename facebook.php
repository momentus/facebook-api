<?php

/* need to add nice facebook api exception handling */

class Facebook {
	public $access_token = "";
	public $application_id = "";
	public $application_secret = "";
	public $code = "";
	public $config = array();
	public $cookiename = "";
	public $facebook_canvas_url = "";
	public $signed_request = "";
	public $redirect_uri;
	
	/* two different facebook domains are used in authenticating */
	public $graph_url = "https://graph.facebook.com";
	public $auth_base_url = "https://www.facebook.com";

  public function __construct($config=array("app_id"=>"","app_secret"=>"")){
		//setup facebook obj
		$this->application_id = $config["app_id"];
    $this->cookiename =  'fbsr_'. $config["app_id"];

		$this->application_secret = $config["app_secret"];
		if(isset($config["fb_canvas_url"])){
			$this->facebook_canvas_url = $config["fb_canvas_url"];
		}
		if(isset($config["redirect_uri"])){
			$this->redirect_uri = $config["redirect_uri"];
		}

	}
	
	public function getCookie(){
		if(isset($_COOKIE[$this->cookiename])){
			$this->signed_request = $this->parse_signed_request($_COOKIE[$this->cookiename]);	
		}
		return $this->signed_request;
	}
	
	
	public function getSignedRequest(){
		/* 
		   If hosted app, get the parsed request
		   Return entire request - because you may need returns from application requests, or other app_data values
		   Also, assign to local properties access token and user id if available.
		*/
		
		if(isset($_REQUEST['signed_request'])){
			$signed_request = $_REQUEST['signed_request'];
			$this->signed_request = $this->parse_signed_request($signed_request);
	
			/* assign frequently used public properties */
			if(isset($this->signed_request["user_id"])){
				$this->uid = $this->signed_request["user_id"];
			}
			if(isset($this->signed_request["oauth_token"])){
				/* will only be present if user has already auth'd app and the token is valid */
				$this->access_token = $this->signed_request["oauth_token"];
			}
			return $this->signed_request;
		}		
	}
	
	public function getAuthUri($perms = ""){
		/* 
			create the url that initiates the authentication process 
		*/
		
		$auth_uri = $this->auth_base_url."/dialog/oauth?";
		$auth_uri .= "client_id=".$this->application_id."&";
		$auth_uri .= "scope=".$perms."&";
		$auth_uri .= "client_secret=".$this->application_secret."&";
		$auth_uri .= "redirect_uri=".urlencode($this->redirect_uri);
		return $auth_uri;
	
	}
	public function getAccessTokenFromCode($code){
		$graph_uri = $this->graph_url . "/oauth/access_token?";
		$graph_uri .= "code=".$code."&";
		$graph_uri .= "client_secret=".$this->application_secret."&";
		$graph_uri .= "client_id=".$this->application_id."&";
		$graph_uri .= "redirect_uri=".urlencode($this->redirect_uri);
		$graph_results = file_get_contents($graph_uri);
		parse_str($graph_results);
		if(isset($access_token)){
			$this->access_token = $access_token;
			return $this->access_token;
		}
	}
	
	public function getAccessToken($params = array()){
		/* a more global way of getting access token
		
		  - if access token in the request obj, assign & return
		  - if code in request obj and not passed in, assign locally
		  - if redirect_uri passed in, assign to local obj
		  - if redirect_uri defined, get access_token from graph with code
		*/
	
		/* already set, just return */
		if(isset($this->access_token) && strlen($this->access_token) > 15){
			return $this->access_token;
		}
		if(isset($_REQUEST['access_token'])){
			$this->access_token = $_REQUEST['access_token'];
			return $this->access_token;
		} else {
		
			/* go down the route for confirming from code */

			/* code not passed in but in request object */
			if(isset($_REQUEST['code']) && (!isset($params["code"]))){
				$code = $_REQUEST['code'];			
			}
			/* passed in */
			if(isset($params["code"])){
				$code = $params["code"];
			}
			/* passed in overrides local assignment */
			if(isset($params["redirect_uri"])){
				$this->redirect_uri = $params["redirect_uri"];
			}
	
			if(isset($this->redirect_uri)){
				$access_token = $this->getAccessTokenFromCode($code);
				return $this->access_token;
			} else {
				return nil;
			}
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
	
	public function api($object = "string", $params = array()){
		if(!$object || $object == ""){
//			$object = "me";
		}
		$paramstr = "";

		if(isset($params)){
			foreach($params as $key=>$value){
				$paramstr .= "&".$key . "=".urlencode($value);				
			}
		} 
		if($this->access_token && !isset($params["access_token"])){
			$params["access_token"] = $this->access_token;
		}
		if($this->access_token){
			if(isset($params["method"]) == "post"){
				/* uploading mainly */
				$ch = curl_init();
				preg_match('/photos/',$object,$result);
				if(count($result)>0){
					$url = $this->graph_url.$object.'?access_token='.$this->access_token;
				} 
				preg_match('/albums/',$object,$result);
				if(count($result)>0){
					$url = $this->graph_url.'/me/albums?access_token='.$this->access_token;
				}
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				$data = curl_exec($ch);
				//returns the photo id
				print_r(json_decode($data,true));		
			} else {
		
				$graph_request = $this->graph_url."/".$object."?access_token=".$this->access_token."&".$paramstr;
				$graph_results = file_get_contents($graph_request);
				$graph_results = json_decode($graph_results);
				return $graph_results;
			}
		}
	} // end api function
	
	
}


?>
