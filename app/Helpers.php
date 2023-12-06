<?php
// start session
session_start();

function setSessionClickIDIntoCSV($clickID = '') {
	$path = 'clickids.csv';
	$row = [getDeviceID(), $clickID];
	if (($open = fopen($path, "a")) !== false) {
		fputcsv($open, $row);
		fclose($open);
	}
}

function getSessionClickIDFromCSV() {
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

function parseBool($value, $defaultValue) {
    if ($value === 'true' || $value === 'false' || $value === true || $value === false) {
        return strtolower($value);
    } else {
        return $defaultValue;
    }
}


function getMyCookie($name) {
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }
    return null;
}


function removeParam($key, $sourceURL) {
    $urlParts = explode('?', $sourceURL, 2);
    $url = $urlParts[0];
    $queryString = isset($urlParts[1]) ? $urlParts[1] : '';

    if ($queryString !== '') {
        $params = [];
        $params_arr = explode('&', $queryString);
        foreach ($params_arr as $param) {
            list($name, $value) = explode('=', $param, 2);
            if ($name !== $key) {
                $params[] = $param;
            }
        }
        if (!empty($params)) {
            $url .= '?' . implode('&', $params);
        }
    }
    return $url;
}

function getURLParam($key) {
	if(isset($_GET[$key])){
        return $_GET[$key];
    }
	return null;
};



function xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession) {
	if(isset($_SESSION['viewOnce']) && $_SESSION['viewOnce'] != 1){
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
	}

    if ($registerViewOncePerSession) {
        $_SESSION["viewOnce"] = 1;
    }
}

function checkIsExistAndSet($clickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain) {
	$ourCookie = getMyCookie($cookieName);
    if (!isset($_COOKIE['ourCookie']) || $_COOKIE['ourCookie'] === null || $_COOKIE['ourCookie'] === '' || !$firstClickAttribution) {
		setMyCookie($cookieName, $clickID, $cookieDuration, $cookieDomain);
	}
}

function setSessionClickID($clickID = '') {
    $_SESSION["rtkclickid"] = $clickID;
}

function setMyCookie($cookieName, $rtkClickID, $cookieDuration, $cookieDomain) {
	date_default_timezone_set("UTC");
    $cookieValue = $rtkClickID;
	$expirationTime = 86400 * $cookieDuration * 1000;
	$dateTimeNow = time();
	setcookie($cookieName, $cookieValue, $dateTimeNow + $expirationTime, $cookieDomain); 
}

function trackWebsite(){

    $defaultCampaignId = $_GET['defaultcampaignid'] ?? "65553e9b3df94c0001af7765";
    $cookieDomain = $_GET['cookiedomain'] ?? "po.trade";
    $registerViewOncePerSession =  isset($_GET['regviewonce']) ? parseBool($_GET['regviewonce'], false) : parseBool("false", false);
    $lastPaidClickAttribution = false;
    $firstClickAttribution = false;
    $attribution = $_GET['attribution'] ?? "lastpaid";
    $cookieName = "rtkclickid-store";
    $cookieDuration = $_GET['cookieduration'] ?? 90;
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
    if ($attribution === 'lastpaid') {
        $lastPaidClickAttribution = true;
    }
    else if ($attribution === 'firstclick')  {
        $lastPaidClickAttribution = false;
        $firstClickAttribution = true;
    }
    else if ($attribution === 'lastclick')  {
        $lastPaidClickAttribution = false;
        $firstClickAttribution = false;
    }
    $ourCookie = getMyCookie('rtkclickid-store');
    
    $rtkClickID = "";

    $locSearch = ltrim($_SERVER['QUERY_STRING'], '?');

    $rtkfbp = getMyCookie('_fbp');
    $rtkfbc = getMyCookie('_fbc');

    $pixelParams = "&" . $locSearch . "&sub19=" . $rtkfbp . "&sub20=" . $rtkfbc;
    $campaignID = getURLParam('cmpid');
    $souceKey = getURLParam('tsource');

    if ($campaignID == null) {
        $campaignID = $defaultCampaignId;
    }

    if ($lastPaidClickAttribution) {
        if ($campaignID != $defaultCampaignId) {
            $firstClickAttribution = false;
        }
        if ($campaignID == $defaultCampaignId) {
            $firstClickAttribution = true;
        }
    }

    $initialSrc = "https://red-track.net/" . $campaignID . "?format=json" . "&referrer=" . $referrer;
    for ($i = 1; $i <= 10; $i++) {
        $initialSrc = removeParam("sub" . $i, $initialSrc);
    }

    $rawData = null;
    $initialSrc = removeParam("cost", $initialSrc);
    $initialSrc = removeParam("ref_id", $initialSrc);

    if (!isset($_GET['rtkcid'])) {
        if(getSessionClickIDFromCSV() == null){
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
            } else {
                $rtkClickID = json_decode($response)->clickid ?? md5(uniqid(rand(), true));
                setSessionClickID($rtkClickID);
                setSessionClickIDIntoCSV($rtkClickID);
                checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
                xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
            }
            curl_close($ch);
        }else{
            $rtkClickID = getSessionClickIDFromCSV();
            checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
            xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
        }
    }else{
        $rtkClickID = $_GET['rtkcid'];
        checkIsExistAndSet($rtkClickID, $firstClickAttribution, $cookieName, $cookieDuration, $cookieDomain);
        xhrrOpenAndSend($rtkClickID, $referrer, $registerViewOncePerSession);
        setSessionClickID($rtkClickID);
        setSessionClickIDIntoCSV($rtkClickID);
    }
}
