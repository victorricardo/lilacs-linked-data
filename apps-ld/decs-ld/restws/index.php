<?php
require($_SERVER['DOCUMENT_ROOT'].'/apps-ld/config.inc');
require_once("userAPI.php");
require('response.php');

$method = $_SERVER['REQUEST_METHOD'];

switch($method){
  case 'GET':
  case 'DELETE':
    $entityType = $_GET['entityType'];
    $request_data = $_GET;
    $format = isset($_GET['format']) ? $_GET['format'] : 'json';
    break;  
  case 'POST':
  case 'PUT':
    $request_data = json_decode(file_get_contents('php://input'),true);
    $entityType = $request_data['entityType'];
    $format = isset($request_data['format']) ? $request_data['format'] : 'json';
    break;
}
//echo json_encode($request_data); exit;

if(isset($entityType)){
  switch($entityType){
    case 'Descriptors':  
    case 'Qualifiers':  
    case 'DescriptorQualifierPairs':  
    case 'Concepts':  
    case 'Terms':  
    case 'TreeNumbers':  
    case 'SupplementaryConceptRecords':
      require_once("entityAPI.php");
      $request_data['table'] = substr($request_data['entityType'], 0, -1);//se quita 's' final
      entityAPI($request_data);
      break;
    case 'api_key'://solicitud de api_key
      insertUser($request_data);//no se chequea permiso, se esta creando usuario
      break;  
    case 'users':
      userAPI($request_data);
      break;
    default:
      response(400, 'Uri incorrecta, no existe el tipo de entidad: '.$_GET['entityType']);
      break;  
  }  
}
else
  response(400, 'Uri incorrecta, especifique el tipo de entidad.');
?>
