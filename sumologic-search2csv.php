<?php
require_once('send-mail.php'); 

// parameters
$sc_default_timezone = "UTC"; 
$sc_BaseURL = "https://api.sumologic.com/api/v1";
$sc_SearchAPI="/logs/search";
$sc_default_period="1 hour ago";
$sc_send_email=FALSE; 

function BuildSearchURL($SearchUrl, $q, $from, $to, $tz) {
	$querystring=http_build_query(array(
						'q' => $q, 
						'from' => $from, 
						'to' => $to, 
						'tz' => $tz
						));
						
	$APIurl = $SearchUrl.$querystring; 
	return $APIurl;
 }

function GetSumoQuery($SearchUrl, $q, $from, $to, $tz, $user, $pwd) {					
	
	function GetURL($url,$user, $pwd ) {
		// This internal function works recursively to resolve 301/302 redirects and keep the authorization token between redirects
		$ch = curl_init();  
	
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_HEADER, false); 
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pwd);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json'
				));

		curl_setopt($ch,CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$json=curl_exec($ch);
		$request_status= curl_getinfo($ch);
		$status_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($status_code == 301 || $status_code == 302 )  {
				return GetURL($request_status['redirect_url'],$user, $pwd ); 
			} else {
				$GLOBALS['http_status'] = $status_code;
				return $json;
			}
	}
	$APIurl=BuildSearchURL($SearchUrl, $q, $from, $to, $tz)  ; 
	
	return GetURL($APIurl,$user, $pwd ) ;
    
}



// *******************************************************************************
// Main Body
// *******************************************************************************
try {
	// check if the search file is provided, else use default
	if($argc>1) {
		$search_file= $argv[1]; 
	} else {
		$search_file= "search.txt"; 
	}
	if (!file_exists($search_file) ) {
		throw new Exception("Search file ".$search_file." not found");
	}

	// check if the configuration file is provided, else use default
	if($argc>2) {
		$ini_file= $argv[2]; 
	} else {
		$ini_file= "sumologic-config.txt"; 
	}
	if (!file_exists($ini_file) ) {
		throw new Exception("Configuration file ".$ini_file." not found");
	}

	// check if the CSV format is required, else use default JSON
	// Anything goes here, but if parameter is not json then 
	if($argc>3) {
		$file_format= strtolower($argv[3]); 
	} else {
		$file_format= "json"; 
	}
	if ( $file_format!="csv" && $file_format!="json" ) {
		throw new Exception("Invalid file format required");
	}
	
	// setup configuration variables
	$ini_array = parse_ini_file($ini_file);
	$user_id = $ini_array['Userid'] ;
	$secret = $ini_array['Password'] ;
	$recipient = $ini_array['Email-To'] ;
	$sender = $ini_array['Email-From'] ;
	$customer=$ini_array['Customer'] ;

	$search=file_get_contents($search_file);

				


	date_default_timezone_set($sc_default_timezone);
	$date = new DateTime();

	// Modify the date settings according to your needs.
	
	// Use c format for hour based reports
	// $today=$date->format('Y-m-d') . "T00:00:00";
	// $date->add(DateInterval::createFromDateString('yesterday'));
	// $fromday = $date->format('Y-m-d') . "T00:00:00";
	$today=$date->format('c');
	$date->add(DateInterval::createFromDateString($sc_default_period));
	$fromday = $date->format('c');


	$json=GetSumoQuery($sc_BaseURL.$sc_SearchAPI."?", $search, $fromday, $today, $sc_default_timezone, $user_id, $secret) ; 
	
	
	// $json=file_get_contents("results.json"); $GLOBALS['http_status'] = 200;
	
	if (empty($GLOBALS['http_status']) ) {
		throw new Exception("HTTP request failed for unknown reasons");
	}
	
	if ($GLOBALS['http_status']>402) {
		throw new Exception("HTTP request failed with code " . $GLOBALS['http_status']);
	}
	
	// $outfile="Sumologic-stats-".$customer."-".$fromday."-".$today.".csv"; 
	$outfile="Sumologic-query-".$customer."-".basename($search_file).".".$file_format; 
if ( $file_format=="json" ) {
		if (!file_put_contents($outfile,$json)) {
				throw new Exception("Error creating file ".$outfile);
		}
	} else {
	$Sumologic = json_decode($json, true);

	if (empty($Sumologic)) { 
		throw new Exception("Query result set is empty. "."HTTP Status is ".$GLOBALS['http_status']);
	}
	
	$f = fopen($outfile, 'w');		
	if (empty($Sumologic[0])) {  // Not an array, probably some application error.  
		$headers=array_keys($Sumologic);
		fputcsv($f, $headers);		
		fputcsv($f, $Sumologic);
	} else {					// all is well
		$headers=array_keys($Sumologic[0]);
		fputcsv($f, $headers);	
		foreach ($Sumologic as $event)
		{ 
			fputcsv($f, $event);
		}
	}
	}
	
	// Check whether to send email
	if ($sc_send_email) {
		$message="Hourly report"; 
		sendfile($recipient, $sender, $subject, $message, $outfile) ; 
	}
	return; 

}

//catch exception
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}



