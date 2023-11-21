<?php
// start session
session_start();

function getDeviceID()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $fingerprint = $userAgent . $acceptLanguage . $ipAddress;
    return md5($fingerprint);
}

function getMyCookie($name) {
	$cookie = isset($_SERVER["HTTP_COOKIE"]) ? $_SERVER["HTTP_COOKIE"] : "";
	$parts = explode("; ", $cookie);
	$ret = "";
	foreach($parts as $part){
		$subparts = explode("=", $part);
		if($subparts[0] == $name){
			$ret = $subparts[1];
		}
	}
	return $ret;
}

function removeParam($key, $sourceURL) {
	$rtn = explode("?", $sourceURL)[0];
	$param = [];
	$params_arr = [];
	$queryString = str_contains($sourceURL, "?") ? explode("?", $sourceURL)[1] : "";
	if ($queryString != "") {
    	$params_arr = explode("&", $queryString);
    	foreach ($params_arr as $index=>$params) {
        	$param = explode("=", $params)[0];
        	if ($param == $key) {
            	array_splice($params_arr, $index, 1);
        	}
    	}
    	$rtn = $rtn . "?" . implode("&", $params_arr);
	}
	return $rtn;
}

function stripTrailingSlash($str) {
	return rtrim($str, "/");
}

function getCurrentLocationSearch() {
	$location = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$location .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	} else {
		$location .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	}
	$locArr = explode("search?", $location);
	return isset($locArr[1]) ? $locArr[1] : "";
}

function getURLParam($key, $sourceURL) {
	$rtn = "";
	$params_arr = [];
	if ($sourceURL != "") {
    	$params_arr = explode("&", $sourceURL);
    	foreach ($params_arr as $index=>$params) {
        	$subparams = explode("=", $params);
        	if ($subparams[0] == $key) {
            	$rtn = isset($subparams[1]) ? $subparams[1] : "";
        	}
    	}
	}
	return $rtn;
}

function setMyCookie($cookieName, $rtkClickID, $cookieDuration, $cookieDomain) {
	date_default_timezone_set("UTC");
    $cookieValue = $rtkClickID;
	$expirationTime = 86400 * $cookieDuration * 1000;
	$dateTimeNow = time();
	setcookie($cookieName, $cookieValue, $dateTimeNow + $expirationTime, $cookieDomain); 
}

function checkIsExistAndSet($clickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain) {
    if (isset($ourCookie) || !$firstClickAttribution) {
		setMyCookie($cookieName, $clickID, $cookieDuration, $cookieDomain);
	}
}

function getSessionRegisterViewOncePerSession() {
    return isset($_SESSION["viewOnce"]) ? $_SESSION["viewOnce"] : '';
}

function setSessionRegisterViewOncePerSession() {
    $_SESSION["viewOnce"] = 1;
}

function getSessionClickID() {
    return isset($_SESSION["rtkclickid"]) ? $_SESSION["rtkclickid"] : '';
}

function setSessionClickID($clickID = '') {
    $_SESSION["rtkclickid"] = $clickID;
}

function setHref($rtkClickID, $referrer) {
	$script = <<<'HEREA'
	<script>
		function stripTrailingSlash(str) {
			return str.replace(/\/$/, "");
		}
		document.querySelectorAll('a').forEach(function (el) {
			if (el.href.indexOf("https://red-track.net/click") > -1) {
				if (el.href.indexOf('?') > -1) {
					el.href = stripTrailingSlash(el.href) + "&clickid=" + ":clickID" + "&referrer=" + ":referrer"
				} else {
					el.href = stripTrailingSlash(el.href) + "?clickid=" + ":clickID" + "&referrer=" + ":referrer"
				}
			}
			if (el.href.indexOf("https://red-track.net/preclick") > -1) {
				if (el.href.indexOf('?') > -1) {
					el.href = stripTrailingSlash(el.href) + "&clickid=" + ":clickID" + "&referrer=" + ":referrer"
				} else {
					el.href = stripTrailingSlash(el.href) + "?clickid=" + ":clickID" + "&referrer=" + ":referrer"
				}
			}
		})
	</script>
	HEREA;
	echo str_replace(':referrer', $referrer, str_replace(':clickID', $rtkClickID, $script));
}

function xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession) {

	// if(!getSessionRegisterViewOncePerSession()){
		$url = "https://red-track.net/view?clickid=" . $rtkClickID . "&referrer=" . $referrer;
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
		}
		curl_close($ch);

		$url1 = "https://red-track.net/preview?clickid=" . $rtkClickID . "&referrer=" . $referrer;
		$ch1 = curl_init();
		curl_setopt($ch1, CURLOPT_URL, $url1);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, 'GET');
		$response1 = curl_exec($ch1);
		if ($response1 === false) {
			// Handle the error
			$error = curl_error($ch1);
			curl_close($ch1);
			die("cURL Error: $error");
		}
		curl_close($ch1);
	// }

    if ($registerViewOncePerSession) {
        setSessionRegisterViewOncePerSession();
    }
}

function trackWebsite(){
	$defaultCampaignId = "65553e9b3df94c0001af7765";
	$cookieDomain = "po.trade";
	$cookieDuration = 90;
	$registerViewOncePerSession = false;
	$lastPaidClickAttribution = false;
	$firstClickAttribution = false;
	$attribution = "lastpaid";
	$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
	if ($attribution === 'lastpaid') {
		$lastPaidClickAttribution = true;
	} else if ($attribution === 'firstclick') {
		$lastPaidClickAttribution = false;
		$firstClickAttribution = true;
	} else if ($attribution === 'lastclick') {
		$lastPaidClickAttribution = false;
		$firstClickAttribution = false;
	}
	
	$ourCookie = getMyCookie('rtkclickid-store');
	$cookieName = "rtkclickid-store";
	$locSearch = getCurrentLocationSearch();
	$rtkfbp = getMyCookie('_fbp');
	$rtkfbc = getMyCookie('_fbc');
	$pixelParams = ($locSearch != '' ? ("&" . $locSearch) : "") . "&sub19=" . $rtkfbp . "&sub20=" . $rtkfbc;
	$campaignID = getURLParam('cmpid', $locSearch);
	$souceKey = getURLParam('tsource', $locSearch);
	if (!isset($campaignID) || $campaignID == "") {
		$campaignID = $defaultCampaignId;
	}
	
	$initialSrc = "https://red-track.net/" . $campaignID . "?format=json&referrer=" . $referrer;
	
	for ($i = 1; $i <= 10; $i++) {
		$initialSrc = removeParam("sub".$i, $initialSrc);
	}
	
	$initialSrc = removeParam("cost", $initialSrc);
	$initialSrc = removeParam("ref_id", $initialSrc);

	if (!getURLParam('rtkcid', $locSearch)) {
		$rtkClickID = "";
		if (!getSessionClickID()) {
			$url = $initialSrc . $pixelParams;
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
			}
			$rtkClickID = isset($response) ? json_decode($response)->clickid : md5(uniqid(rand(), true));
			setSessionClickID($rtkClickID);
			checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
			setHref($rtkClickID, $referrer);
			xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
			curl_close($ch);
		} else {
			$rtkClickID = getSessionClickID();
			checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
			setHref($rtkClickID, $referrer);
			xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
		}
	} else {
		$rtkClickID = getURLParam('rtkcid', $locSearch);
		checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
		xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
		setHref($rtkClickID, $referrer);
		setSessionClickID($rtkClickID);
	}

}