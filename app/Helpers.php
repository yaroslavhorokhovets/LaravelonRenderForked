<?php
// start session
session_start();

function getDeviceID()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
	$method = $_SERVER['REQUEST_METHOD'];
	$sec_ch_ua = $_SERVER['HTTP_SEC_CH_UA'];
	$http_accept = $_SERVER['HTTP_ACCEPT'];

    $fingerprint = $userAgent . $acceptLanguage . $ipAddress . $method . $sec_ch_ua . $http_accept;
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

function getURLParam($key) {
	return isset($_GET[$key]) ? $_GET[$key] : '';
}

function setSessionRegisterViewOncePerSession() {
    $_SESSION["viewOnce"] = 1;
}

function getSessionClickID() {
	$path = 'clickids.csv';
	$array = [];
	if(file_exists($path)){
		if (($open = fopen($path, "r")) !== false) {
			while (($data = fgetcsv($open, 1000, ",")) !== false) {
				$array[] = $data;
			}
			fclose($open);
		}
		// Compare fingerprint data
		foreach($array as $arr){
			if(isset($arr[0]) && isset($arr[1]) && $arr[0] == getDeviceID()){
				return $arr[1];
			}
		}
	}
	return null;
}

function setSessionClickID($clickID = '') {
	$path = 'clickids.csv';
	$row = [getDeviceID(), $clickID];
	if (($open = fopen($path, "a")) !== false) {
		fputcsv($open, $row);
		fclose($open);
	}
}

function setHref($rtkClickID, $referrer) {
	$script = <<<'HEREA'
	<script>
		function stripTrailingSlash(str) {
			return str.replace(/\/$/, "");
		}
		document.querySelectorAll('a').forEach(function (el) {
			if (el.href.indexOf("https://track.red-track.net/click") > -1) {
				if (el.href.indexOf('?') > -1) {
					el.href = stripTrailingSlash(el.href) + "&clickid=" + ":clickID" + "&referrer=" + ":referrer"
				} else {
					el.href = stripTrailingSlash(el.href) + "?clickid=" + ":clickID" + "&referrer=" + ":referrer"
				}
			}
			if (el.href.indexOf("https://track.red-track.net/preclick") > -1) {
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

	if(!isset($_SESSION["viewOnce"]) || $_SESSION["viewOnce"] != 1) {
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
	}

    if ($registerViewOncePerSession) {
        setSessionRegisterViewOncePerSession();
    }
}

function trackWebsite(){
	$defaultCampaignId = $_GET['defaultcampaignid'] ?? "65553e9b3df94c0001af7765";
	$cookieDomain = $_GET['cookiedomain'] ?? "po.trade";
	$cookieDuration = $_GET['cookieduration'] ?? 90;
	$registerViewOncePerSession = $_GET['regviewonce'] ?? false;
	$lastPaidClickAttribution = false;
	$firstClickAttribution = false;
	$attribution = $_GET['attribution'] ?? "lastpaid";
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
	
	$cookieName = "rtkclickid-store";
	$ourCookie = getMyCookie($cookieName);
	$locSearch = getCurrentLocationSearch();
	$rtkfbp = getMyCookie('_fbp');
	$rtkfbc = getMyCookie('_fbc');
	$pixelParams = ($locSearch != '' ? ("&" . $locSearch) : "") . "&sub19=" . $rtkfbp . "&sub20=" . $rtkfbc;
	$campaignID = getURLParam('cmpid');
	$souceKey = getURLParam('tsource');
	if ($campaignID == "") {
		$campaignID = $defaultCampaignId;
	}
	
	$initialSrc = "https://red-track.net/" . $campaignID . "?format=json&referrer=" . $referrer;
	
	for ($i = 1; $i <= 10; $i++) {
		$initialSrc = removeParam("sub".$i, $initialSrc);
	}
	
	$initialSrc = removeParam("cost", $initialSrc);
	$initialSrc = removeParam("ref_id", $initialSrc);

	if (!getURLParam('rtkcid')) {
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
			setHref($rtkClickID, $referrer);
			xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
			curl_close($ch);
		} else {
			$rtkClickID = getSessionClickID();
			setHref($rtkClickID, $referrer);
			xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
		}
	} else {
		$rtkClickID = getURLParam('rtkcid');
		xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
		setHref($rtkClickID, $referrer);
		setSessionClickID($rtkClickID);
	}

}