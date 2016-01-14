<?php
/*********************************************************
VERSION: API_Proxy.php ver 0.9

PURPOSE:
	This file acts as An intermediary proxy to allow FMP to make more complicated API calls
	Can use POST or GET

TODO:
	-after instal set up CA certs for cURL see line with  CURLOPT_SSL_VERIFYPEER	
	-debug CURLOPT_DELETE isss
	-Add DEBUG mode that shows the data being passed?? simular to Requestbin.in

PARAMETERS: 
			( * Needed )
	api_mode 	= * request mode { GET | POST | PUT | DELETE }
	api_url 	= * the creds and full URL being called eg: user:key@https://store-r1dssj.mybigcommerce.com/api/v2/time
	api_user	= * Username or ID for basic auth
	api_pass	= * password or key for basuc auth
	
	OPTIONAL
	api_form	= XML or JSON for media response type (default: JSON)
  	api_headers	= any needed data that will go into the headers, each header row separated by '~' tilde
	api_body	= XML or JSON body dada

RETURNS:
raw data from the called API in the same Format

AUTHOR:
Charles Delfs - www.delfsengineering.ca 
				cdelfs@delfsengineering.ca
				
MORE NOTES:
	target server for this file must have an updates certs files in cURL see the following link
	http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
	If an HTTP error happens (Not a 200 series error) then there is 

**********************************************************/

// fetch url request params

$api_headers 	= "";
$headersArray = [];
$api_body 		= "";
$api_form		= "JSON";  // default if this is not passed

// ============   convert all the REQUEST params into local $Vars ============
foreach($_REQUEST as $key=>$value) {
	$$key = $value;
}

// ================ Uncomment to show request params Array =============
//print_r($_REQUEST);
//exit();	

//  Check for missing needed Params 
if ( !isset($api_mode) or !isset($api_url) or !isset($api_user)  or !isset($api_pass) ) {
	die("Error: Missing parameters");
}

// Convert Headers to array if any for cURL
if (isset($api_headers)) {
	$headers_array = explode("~" , $api_headers);
}

// add any additional headers 
Array_push ( $headersArray, $api_form == "XML" ? "Content-type: application/xml" : "Content-type: application/json");
Array_push ( $headersArray, $api_form == "XML" ? "Accept: application/xml" : "Accept: application/json");

// Add any request parameters in from the Request array but leave out the ones we use locally
$query  = $_REQUEST;
// unset (remove) any local items that dont need to be passed through
unset($query['api_mode'], $query['api_url'], $query['api_user'], $query['api_pass'], $query['api_form'], $query['api_headers'], $query['api_body'], $query['__utma'], $query['__utmz']);
if (is_array($query)) {
	$api_url .= '?' . http_build_query($query);  // add parameters to the URL
}

// ============ Make the Call ======================
$ch = curl_init($api_url);  //open connection
// curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0"); // if we want to pass a specific 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  	//optional, sends response to a variable
// curl_setopt($curl, CURLOPT_HEADER, false ); 	//True if we are to include the header in the output from the foreign call
curl_setopt($ch, CURLOPT_TIMEOUT, 60 );  		//set to 60 seconds from BC API Guide v1 PDF example execution time
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60 );	// time to connect
curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArray );  //load all header data
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $api_mode );  // get, post, put, Delete
curl_setopt($ch, CURLOPT_USERPWD, "$api_user:$api_pass"); // add the basic auth cred to the header in base64
curl_setopt($ch, CURLOPT_POSTFIELDS, $api_body );

// ======================================issue with this still
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // temp because server does not have cert installed

// Mode specific settings
switch ($api_mode) {
    case "GET":
        // misc get stuff
        break;
    case "POST":
        curl_setopt($ch, CURLOPT_POST, true ); 
        break;
    case "PUT":
        curl_setopt($ch, CURLOPT_PUT, true );
		$handle = tmpfile();
        fwrite($handle, $api_body);
        fseek($handle, 0);
        curl_setopt($ch, CURLOPT_INFILE, $handle);
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($api_body));
        break;
	case "DELETE":
		break;
    default:
		die("Error: 'mode' not valid"); // Mode not found so help by telling dev
}

$result = curl_exec($ch);  //execute post
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

// Check for error * 200 codes are successes
/*
if ( $status < 200 or $status >299 ) {
    die("Error: Call to URL " . $api_url . $query. "failed with status " . $status . "curl_error " . curl_error($ch) . "curl_errno " . curl_errno($ch) . "Response: " .  $result);
}
*/

if ( $status < 200 or $status >299 ) {
    die("Error: call to URL $api_url \nfailed with status $status, \ncurl_error " . curl_error($ch) . "\nurl_errno " . curl_errno($ch) . "\nresponse: " . $result); 
}

curl_close($ch);  

// Echo results
echo ($result) . PHP_EOL;


?>
