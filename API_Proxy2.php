<?php
/*********************************************************
VERSION: API_Proxy.php ver 2.0

PURPOSE:
	This file acts as An intermediary proxy to allow FMP to make more complicated API calls
	Can use POST or GET

TODO:
	-after instal set up CA certs for cURL see line with  CURLOPT_SSL_VERIFYPEER	
	-debug CURLOPT_DELETE isss
	

PARAMETERS: 
			( * Needed )
	api_mode 	= * request mode { GET | POST | PUT | DELETE }
	api_url 	= * the creds and full URL being called eg: user:key@https://store-r1dssj.mybigcommerce.com/api/v2/time
	
	OPTIONAL
	api_user	= Username or ID for basic auth
	api_pass	= password or key for basuc auth
	api_headerX	= any single header line, X is index number 1,2,3 etc
	api_body    = XML or JSON body dada
	api_timeout = number of seconds to wait for the connection, 60 if not passed
	api_file    = path and name of file to POST
	api_debug   = 1 to enable echoing of debugging info

RETURNS:
raw data from the called API in the same Format

AUTHOR:
Charles Delfs - www.delfsengineering.ca 
				cdelfs@delfsengineering.ca
				 
MORE NOTES:
	target server for this file must have an updates certs files in cURL see the following link
	http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
	If an HTTP error happens (Not a 200 series error) then it is passed back
	
REVISIONS:
    01/18/2016 	- Added timeout control, php = infinity, $api_timeout as parameter
    03/021/201	- Added api_header key for passing any single header, Debug mode
  				  check for passed posted file and pass that along
  

**********************************************************/

set_time_limit(0);// to infinity 
// fetch url request params

$api_headers 	= "";
$api_debug 		= false;
$headersArray   = [];
$query 			= [];
$api_body 		= "";
$api_form		= "JSON";  // default if this is not passed
$api_timeout 	= 60 ; // timeout seconds default if not passed, else use passed value

// ============   convert all the REQUEST params into local $Vars ============
foreach($_REQUEST as $key=>$value) {
//   add any headers 
  if(substr($key, 0, 10) == "api_header"){
    Array_push ( $headersArray, $value);
  } elseif (substr($key, 0, 4) == "api_") {
    	$$key = $value; 
  } else {  	
    	$query[$key] = $value; // build query array with NON api_* params
	}
}

debug("\$_REQUEST",$_REQUEST);

//  Check for missing needed Params 
if ( !isset($api_url) || !isset($api_mode) ) {
	die("Error: Missing parameters required: api_url, api_mode,  ");
}


// if a file was passed, pass it along too
if( count($_FILES) ) {
    debug("\$_File submitted:",$_FILES);
    // Make suere there is not a file AND regular body (files are posted as a form)
    if($api_body !== '' && $api_mode !='POST'){
    	Die('Error: You have included a file, api_mode must be "POST" ');	
    }
   
	$formFileName = key($_FILES);
	$filePath = '@'. $_FILES[$formFileName]['tmp_name'] . ';filename=' . $_FILES[$formFileName]['name']  . ';type=' . $_FILES[$formFileName]['type'];
	$query[$formFileName] = $filePath; // add the file onto the query string if set
}

debug("\$query",$query);

// Adjust the URL if this is GET
if (count($query) && $api_mode == "GET") {
	$api_url .= '?' . http_build_query($query);  	// add parameters to the URL
}
 
// ============ Make the Call ======================
$ch = curl_init($api_url);  //open connection
// curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0")	 	// if we want to pass a specific browser agent
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  	        	//optional, sends response to a variable
// curl_setopt($curl, CURLOPT_HEADER, false ); 	        	//True if we are to include the header in the output from the foreign call
curl_setopt($ch, CURLOPT_TIMEOUT, $api_timeout );  			//set to 60 seconds 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $api_timeout );	// time to connect
curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArray );       //load all header data
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $api_mode );        // get, post, put, Delete
curl_setopt($ch, CURLOPT_USERPWD, isset($api_user) ? "$api_user:$api_pass" : '' );   // add the basic auth cred to the header if present
// ====================================== issue with this still=====================================
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 			// temp because server does not have cert installed

// Mode specific additional settings
switch ($api_mode) {
	case "GET":
		// nothing to do for GET
		break;
		
	case "POST":
		curl_setopt($ch, CURLOPT_POST, true ); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query );
		break;
		
	case "PUT":
		curl_setopt($ch, CURLOPT_PUT, true );
		$handle = tmpfile();
		fwrite($handle, $api_body);
		fseek($handle, 0);
		curl_setopt($ch, CURLOPT_INFILE, $handle);
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen($api_body));
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $query );
		break;
		
	case "DELETE":
		break;
		
  default:
		die("Error: '&api_mode=' not valid"); // Mode not found so help by telling dev
}

$result = curl_exec($ch);  //execute post
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

// Check for error * 200 codes are successes
if ( $status < 200 or $status >299 ) {
    die("Error: call to URL $api_url failed with status $status, curl_error \"" . curl_error($ch) . "\", url_err_no " . curl_errno($ch) . ", response: " . $result); 
}

curl_close($ch);  

// Echo results
echo ($result) . "<BR />";


// Allows simple debugging info
function debug($name, $var) {
	global $api_debug;
	if( $api_debug=='1' ){
		echo "Debugging '".$name."'" ;
		var_dump($var);
		return ; 
		}
	}

?>
