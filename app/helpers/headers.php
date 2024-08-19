<?php
 
header ("Access-Control-Allow-Origin: * ") ;
header ("Access-Control-Allow-Headers: Origin, Content-Type,Accept,Authorization");
header ("Content-Type: application/json");

include_once('jwt_helper.php');

$JWT_token = 'non_authorised';
$raw = file_get_contents('php://input');
$data = json_decode($raw,true);
 
$apiDb = '';
    
 if(json_encode($data) === 'null') {
    
	$apiDb = $_POST['requestID'];


}else{

  $apiDb = $data['requestID'];

}




if (($apiDb != 'link_54321')) {
	$response = array(
	  'status' => 'false',

	  'message' => 'incomplete request !-hfile',

	);

	print_r(json_encode($response));
	exit;
  }

// $apiDb = 'link_54321';
define('DB_NAME', 'api_'.$apiDb );
define('ASSETS', 'assets_'.$apiDb);
	
?>