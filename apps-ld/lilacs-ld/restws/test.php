<?php
$data['@id'] = 'http://lilacs.sld.cu/documents/15';
$data['id'] = 15;
$data['@type'] = "Article";
echo json_encode($data);
exit;

echo "DOCUMENT_ROOT:". $_SERVER['DOCUMENT_ROOT'];
exit;

require_once("conexionDB.php");
require_once("commonAPI.php");

//$data = file_get_contents("c:/Apache24/htdocs/lilacsRestWS/data.json") ;
$r = mysqli_query($link, "SELECT data FROM Document WHERE id=4");
$r = mysqli_fetch_row($r);
$data = $r[0];
//echo $data;
$data_arr = json_decode($data,true);
/*if($data_arr) {
  print_r($data_arr) ;
}
else
  echo "error json_decode";
*/
    //obtener data preparado para pasar por JSON_OBJECT y solo con propiedades permitidas en lilacs, 
    $data_str = getAllowedData($data_arr);
    $data_type = "$.\"@type\"";  //escapando @
echo  "INSERT INTO document SET dbName = $data->>'$.database'"; exit;   
exit;
    $r = mysqli_query($link, "INSERT INTO document SET data = JSON_OBJECT($data_str), dbName = JSON_EXTRACT('$data','$.database'), type = JSON_EXTRACT('$data','$data_type'), dateCreated = NOW()");

/*$data = getAllowedData($data_arr);
    $r = mysqli_query($link, "INSERT INTO document SET data = JSON_OBJECT($data), dateCreated = NOW()");
    if($r){//OK
echo "insert OK";
      $last_id = mysqli_insert_id($link);
      $type = "$.\"@type\"";  //escapando @
      //actualizar type y dbName con propiedades de data, ->> para quitar ""
      $r = mysqli_query($link, "UPDATE document SET dbName = data->>'$.database', type = data->>'$type' WHERE id=". $last_id);
if($r) 
  echo "update _id, db y type OK";
    }
*/
exit;

//echo $data;
//echo "SELECT JSON_VALID('$data')";
//$r = mysqli_query($link, "SELECT JSON_VALID('$data')");

//$r = mysqli_query($link, "SELECT JSON_VALID('".'{"a": 1}'."')");

$required = "'$.name', '$.author', '$.inLanguage', '$.about', '$.primarySubject', '$.\"@type\"', '$.typeOfLiterature', '$.levelOfTreatment', '$.provider', '$.database'";
//echo "SELECT JSON_CONTAINS_PATH('$data', 'all', $required)"; exit;
$r = mysqli_query($link, "SELECT JSON_CONTAINS_PATH('$data', 'all', $required)");
print_r($r);  
$r = mysqli_fetch_row($r);
print_r($r);  
    if( $r[0]){
      $required = str_replace('$.', '', $required);
      echo "This properties are required $required to create a document";
    }


?>
