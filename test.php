<?php
require("/var/www/html/tools/facebook-api/facebook.php");
include("/var/www/html/tools/dBug.php");


/* create the facebook object */
$facebook = new Facebook(array('app_id'=>"YOUR APP ID", 'app_secret'=>"YOUR SECRET",'fb_canvas_url'=>"YOUR APP CANVAS URL"));

/* pass the redirect uri (has to mathc up with fb's app domain) */
$this_page = $facebook->facebook_canvas_url;
$auth_uri = $facebook->getAuthUri($this_page,"publish_stream,user_photos");

$signed_request = $facebook->getSignedRequest();
?>
<hr>
<h1>Already Auth'd or In a Frame?</h1>
Check your user's id and see if they've authenticated:<br />
<?php

$uid = $facebook->checkRequest();
echo $uid;

?>
<hr>
<h1>Authenticate Your App</h1>
<a href="<?php echo $auth_uri;?>" target="_top">Link to auth app</a>
</br>
<hr>
<h1>Get Data from Facebook Graph</h1>

<?php

$photos = $facebook->api("me/photos", array("fields"=>"images", "limit"=>"10"));
if($photos->data){
	foreach($photos->data as $photo){
		foreach($photo->images as $image){
			if($image->width < 100){
				echo "<img src=\"".$image->source."\" style='position:float;float:left;margin:5px;padidng:5px;' />";
			}
		}
	}
}
?>