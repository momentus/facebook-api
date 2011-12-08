<?php
require("/var/www/html/tools/facebook-api/facebook.php");
include("/var/www/html/tools/dBug.php");

?>
<html>
<head>
<style>
body {font-family:arial;margin-left:30px;}
img {position:relative;float:left;margin:10px;padding:3px;}
code {color:blue;font-size:18px;margin:5px;display:block;}
.output { background-color:#ababab;border:1px solid black;margin:10px;padding:10px;min-width:250px;min-height:80px;}
</style>
<body>
<?php
	

/* create the facebook object */

$facebook = new Facebook(
	array(
	'app_id'=>"your id", 
	'app_secret'=>"your secret",
	'fb_canvas_url'=>"https://apps.facebook.com/yourapp/",
	'redirect_uri'=>"https://apps.facebook.com/yourapp/test.php"
	)
);

$signed_request = $facebook->getSignedRequest();

?>
<h1>FacebookAPI</h1>


<h2>First, instantiate the Facebook object:</h2>
Pass in the application id, secret, canvas URL (if canvas), and the redirect uri. The redirect uri is required for authentication.
<code><xmp>
$facebook = new Facebook(
	array(
		'app_id'=>"My app id", 
		'app_secret'=>"my app secret",
		'fb_canvas_url'=>"https://apps.facebook.com/yourappnamespace/",
		'redirect_uri'=>"https://apps.facebook.com/yourappnamespace/yourpage.php"
	)
);</xmp></code>

<h2>Already Auth'd or In a Frame?</h2>
If your app is in a frame,tab, or canvas, you may want to see what's in the signed request- which includes the user's minimum or max age, ID, etc. This also will set the local access token if it's there. This is handy for a flow for users that haven't authenticated, or for returning users, to forward them to another page.<br>

<code>$signed_request = $facebook->getSignedRequest();</code>
<br>
You may want to simply check and validate the request. This method returns the user's Facebook ID. This is similar to PHP-SDK's "getUser()" method. If the user has authenticated, it establishes the tokens, if not, it still returns the ID. This will also manually check back with Facebook if the user has authenticated the app. Good for out-of-frame apps.
<br>
<code>echo $facebook->checkRequest();</code> 
<br>
You can also explicitly get the access token (and set it to fb obj) via:<br>
<code>$facebook->getAccessTokenFromCode($code,$redirect_uri)</code>
<br />
<center><div class="output">Your UID: <strong>
<?php
echo $facebook->checkRequest();
?></strong></div></center>
<hr>
<h2>Authenticate Your App</h2>
Use this url to log users in, and accept permissions.
<code>
$auth_uri = $facebook->getAuthUri("publish_stream,user_photos");
</code>
<?php 

$auth_uri = $facebook->getAuthUri("publish_stream,user_photos");

?>
<center><div class="output"><a href="<?php echo $auth_uri;?>" target="_top">Login to Authenticate App</a></div></center>
</br>
<hr>
<h2>Get Data from Facebook Graph</h2>
This is a simple api request:<br />
<code>$photos = $facebook->api("me/photos", array("fields"=>"images", "limit"=>"10"));</code>
The first argument is the Graph Object- the User, the photo, the event, etc. The second argument is an array containing any Graph parameters you wish- "since" date to narrow results, here I've passed "limit" to limit the result, and "fields" to show only the attributes of the object I'm interested in.
<br>
The result is a standard object format (it's the default json decode format). 

<code><xmp>
$photos = $facebook->api("me/photos", array("fields"=>"images", "limit"=>"10"));
if($photos->data){
	foreach($photos->data as $photo){
		foreach($photo->images as $image){
			echo "<img src=\"".$image->source."\" />";
		}
	}
}
</xmp>
</code>
<br>
<div class="output" style="width:500px;height:200px;">
<?php

$photos = $facebook->api("me/photos", array("fields"=>"images", "limit"=>"10"));
if($photos->data){
	foreach($photos->data as $photo){
		foreach($photo->images as $image){
			if(($image->width < 100) && ($image->height < 100)){
				echo "<img src=\"".$image->source."\" style='position:float;float:left;margin:5px;padidng:5px;' />";
			}
		}
	}
}
?></div>