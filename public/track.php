<?php

// Start the session
session_start();

function generateRandomString($length = 14) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getPageTitle($url) {
    $html = file_get_contents($url);
    if ($html === false) {
        return "Error: Unable to retrieve the webpage.";
    }

    // Extract title tag using regular expression
    preg_match("/<title>(.*?)<\/title>/i", $html, $matches);
    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return "Error: No title found on the webpage.";
    }
}

function getDocumentLocation() {	

	// Check if HTTPS is used
	$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

	// Check if request was made over HTTPS via a proxy
	$isSecure = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || $isHttps;

	// Get the protocol (http:// or https://)
	$protocol = $isSecure ? 'https://' : 'http://';

	// Get the host name
	$host = $_SERVER['HTTP_HOST'];

	// Get the request URI (including query strings)
	$uri = $_SERVER['REQUEST_URI'];

	// Construct the full URL
	$fullUrl = $protocol . $host . $uri;

	return $fullUrl;
}

function sendRequest()
{
    // Get the event name from the incoming request.
	$eventName = isset($_REQUEST['event']) ? $_REQUEST['event'] : "PageView"; // or FormSubmit, AddToCart, OutboundClicks

	$propertyid = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : "iMaKRTVWjJ5T";
	// Get the current DateTime object
	$dateTime = new DateTime();
	// Get the current timestamp in milliseconds
	$timestampInMillis = $dateTime->format('Uv');

	// Get the title from the incoming request.
	$title = isset($_REQUEST["title"]) ? $_REQUEST["title"] : "";

	// Generate the reqeust URL
	$url = "https://t1.anytrack.io/assets/";
	$url .= $propertyid . "/collect?";
	$url .= "cid=" . generateRandomString(14);
	$url .= "&ts=" . $timestampInMillis;
	$url .= "&nc=" . 1;
	$url .= "&en=" . rawurlencode($eventName);
	$url .= "&dl=" . rawurlencode(getDocumentLocation());
	$url .= "&dt=" . rawurlencode($title);
	$url .= "&dr=" . rawurlencode($title);

	// Check if the query string exists
	if(isset($_SERVER['QUERY_STRING'])) {
		// Append query string to the reqeust URL
		$url .= "&" . $_SERVER['QUERY_STRING'];
	}

	// If the user is already logged in, the external ID is obtained from the user information.
	if(isset($_SESSION['user_id'])) {
		$externalID = $_SESSION['user_id'];
	}
	$url .= isset($externalID) ?? "&rid=" . $externalID;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	if ($response === false) {
		// Handle the error
		$error = curl_error($ch);
		curl_close($ch);
		die("cURL Error: $error");
	} else {
		var_dump(array(
            "URL" => $url,
            "Event Name" => $eventName,
            "Response" => $response
        ));
	}
	curl_close($ch);
}

sendRequest();
